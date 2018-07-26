<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use DivineOmega\PasswordExposed\PasswordStatus;
use Kelunik\TwoFactor\Oath;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\InputFilterContainer;
use ParagonIE\Ionizer\InvalidDataException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\Exceptions\SecurityException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Utility;
use Soatok\Website\FilterRules\Den\RegisterFilter;
use Soatok\Website\Middleware\AutoLoginMiddleware;
use Soatok\Website\Struct\User;

/**
 * Class Register
 * @package Soatok\Website\RequestHandler\Den
 */
class Register implements RequestHandlerInterface
{
    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new RegisterFilter();
    }

    /**
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddleware(): array
    {
        return [
            new AutoLoginMiddleware()
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
     * @param array $post
     *
     * @throws BaseException
     * @throws SecurityException
     * @throws \SodiumException
     * @return User
     */
    protected function createAccount(array $post): User
    {
        $db = GlobalConfig::instance()->getDatabase();
        if (User::usernameIsTaken($post['username'])) {
            throw new SecurityException(
                'User "' . $post['username'] . '" is already registered.'
            );
        }
        if (\password_exposed($post['passphrase']) === PasswordStatus::EXPOSED) {
            throw new SecurityException(
                'The password you attempted to register with has already been ' .
                'compromised in a previous data breach. Please visit ' .
                '<a href="https://haveibeenpwned.com">Have I Been Pwned?</a> ' .
                'for more information.'
            );
        }

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

        $user = new User($db);
        $user->set('username', $post['username'])
             ->set('email', $post['email']);
        if (!$user->create()) {
            throw new SecurityException('Could not create new user account.');
        }
        $user->setPassword(new HiddenString($post['passphrase']));
        $user->setTwoFactorSecret(new HiddenString($_SESSION['twoFactorTemp']));
        $user->update();
        \session_regenerate_id(true);
        $_SESSION['userid'] = $user->getId();
        $_SESSION['logout-nonce'] = Base64UrlSafe::encode(
            \random_bytes(33)
        );
        return $user;
    }

    /**
     * Process an HTTP request, produce an HTTP response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws InvalidDataException
     * @throws BaseException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if (isset($_SESSION['userid'])) {
            return Utility::redirect('/den');
        }
        $post = Utility::getParams($request);
        $twigVars = [];

        if ($post) {
            try {
                $this->createAccount($post);
                return Utility::redirect('/den');
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
            '$username'
        );

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/register.twig', $twigVars);

    }
}
