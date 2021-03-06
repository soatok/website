<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use GuzzleHttp\Psr7\Response;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    NoSuchUserException,
    SecurityException
};
use Soatok\Website\Engine\{
    Contract\MiddlewareInterface,
    GlobalConfig,
    Traits\RequestHandlerTrait,
    Utility
};
use Soatok\Website\FilterRules\Den\LoginFilter;
use Soatok\Website\Middleware\AutoLoginMiddleware;
use Soatok\Website\Struct\User;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class Login
 * @package Soatok\Website\RequestHandler\Den
 */
class Login implements RequestHandlerInterface
{
    use RequestHandlerTrait;

    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new LoginFilter();
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
     * @param array $params
     * @return Response
     *
     * @throws BaseException
     * @throws SecurityException
     * @throws \SodiumException
     */
    protected function login(array $params): Response
    {
        if (!isset($params['username'], $params['passphrase'])) {
            throw new SecurityException('Form not completed.');
        }
        $username = $params['username'];
        $passphrase = new HiddenString($params['passphrase']);

        $user = User::byUsername($username);
        if (!$user->checkPassword($passphrase)) {
            throw new NoSuchUserException('Invalid username and/or passphrase');
        }
        if (!$user->checkSecondFactor($params['authcode'])) {
            /** @todo log this a huge red flag about password theft */
            throw new NoSuchUserException('Invalid 2FA code provided.');
        }

        \session_regenerate_id(true);
        $_SESSION['userid'] = $user->getId();
        $_SESSION['logout-nonce'] = Base64UrlSafe::encode(\random_bytes(33));
        if (isset($params['remember'])) {
            $token = $user->createAuthToken();
            Utility::setCookie('auth', $token->getString(), time() + 604800);
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
     * @throws SyntaxError
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if (isset($_SESSION['userid'])) {
            return Utility::redirect('/den');
        }
        $post = $this->getPostData();
        $twigVars = [];
        if ($post) {
            try {
                return $this->login($post);
            } catch (\Throwable $ex) {
                if (GlobalConfig::instance()->isDebug()) {
                    $twigVars['error'] = \get_class($ex) . ': ' .
                         $ex->getMessage() . PHP_EOL .
                         $ex->getFile() . PHP_EOL .
                         'Line ' . $ex->getLine() . PHP_EOL .
                         '<pre>'. $ex->getTraceAsString() . '</pre>';
                } else {
                    $twigVars['error'] = $ex->getMessage();
                }
            }
        }

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/login.twig', $twigVars);

    }
}
