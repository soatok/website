<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use Kelunik\TwoFactor\Oath;
use ParagonIE\ConstantTime\Base32;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\{
    InputFilterContainer,
    InvalidDataException
};
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    RaceConditionException,
    SecurityException
};
use Soatok\Website\Engine\{Contract\MiddlewareInterface,
    GlobalConfig,
    Traits\RequestHandlerTrait,
    Utility};
use Soatok\Website\FilterRules\Den\MyAccountFilter;
use Soatok\Website\Middleware\{
    AutoLoginMiddleware,
    DenMiddleware
};
use Soatok\Website\Struct\User;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class MyAccount
 * @package Soatok\Website\RequestHandler\Den
 */
class MyAccount implements RequestHandlerInterface
{
    use RequestHandlerTrait;

    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new MyAccountFilter();
    }

    /**
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddleware(): array
    {
        return [
            new AutoLoginMiddleware(),
            new DenMiddleware()
        ];
    }

    /**
     * @param array $vars
     * @return RequestHandlerInterface
     */
    public function setVars(array $vars): RequestHandlerInterface
    {
        return $this;
    }

    /**
     * @param User $user
     * @param array $post
     *
     * @return ResponseInterface
     * @throws BaseException
     * @throws SecurityException
     * @throws RaceConditionException
     * @throws \SodiumException
     */
    public function updateAccount(User $user, array $post = []): ResponseInterface
    {
        $changes = false;
        if (isset($post['email'])) {
            $changes = true;
            $user->email = $post['email'];
        }
        if (isset($post['passphrase'])) {
            $changes = true;
            $user->setPassword(new HiddenString($post['passphrase']));
        }
        if (!empty($post['2fa-toggle']) && !empty($post['authcode'])) {
            $changes = true;
            $oath = new Oath();
            if (!isset($_SESSION['twoFactorTemp'])) {
                throw new SecurityException(
                    'Two-factor secret not stored in local session.'
                );
            }
            if (!$oath->verifyTotp($_SESSION['twoFactorTemp'], $post['authcode'])) {
                throw new SecurityException(
                    'Invalid two-factor authentication code.'
                );
            }
            $user->setTwoFactorSecret(
                new HiddenString($_SESSION['twoFactorTemp'])
            );
        }
        if ($changes) {
            $user->update();
        }
        return Utility::redirect('/den');
    }

    /**
     * Process an HTTP request, produce an HTTP response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws BaseException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SecurityException
     * @throws SyntaxError
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $user = User::active();
        $post = $this->getPostData();
        $twigVars = [
            'email' => $user->email,
            'username' => $user->username
        ];

        if ($post) {
            try {
                return $this->updateAccount($user, $post);
            } catch (\Throwable $ex) {
                if (GlobalConfig::instance()->isDebug()) {
                    $twigVars['error'] = \get_class($ex) . ': ' .
                                         $ex->getMessage() . PHP_EOL .
                                         $ex->getTraceAsString() . PHP_EOL;
                } else {
                    $twigVars['error'] = $ex->getMessage();
                }
            }
        }
        $oath = new Oath();
        if (!isset($_SESSION['twoFactorTemp'])) {
            $_SESSION['twoFactorTemp'] = $oath->generateKey();
        }
        $twigVars['twofactoruri'] = $oath->getUri(
            $_SESSION['twoFactorTemp'],
            'soatok.com',
            $user->username
        );
        $twigVars['twofactorsecret'] = Base32::encode($_SESSION['twoFactorTemp']);

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/my-account.twig', $twigVars);
    }
}
