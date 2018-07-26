<?php
declare(strict_types=1);
namespace Soatok\Website\Middleware;

use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\RequestInterface;
use Soatok\Website\Engine\Contract\MiddlewareInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Utility;
use Soatok\Website\Struct\User;

/**
 * Class AutoLoginMiddleware
 * @package Soatok\Website\Middleware
 */
class AutoLoginMiddleware implements MiddlewareInterface
{
    /**
     * @param HiddenString $cookie
     * @return void
     *
     * @throws BaseException
     * @throws \SodiumException
     */
    protected function attemptAutoLogin(HiddenString $cookie)
    {
        try {
            $user = User::byAuthToken($cookie);
            $_SESSION['userid'] = $user->getId();
            $token = $user->createAuthToken();
            Utility::setCookie('auth', $token->getString(), time() + 604800);
        } catch (BaseException | \SodiumException $ex) {
            if (GlobalConfig::instance()->isDebug()) {
                throw $ex;
            }
        }
    }

    /**
     * Middleware acts on requests before the handler.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     *
     * @throws BaseException
     * @throws \SodiumException
     */
    public function __invoke(RequestInterface $request): RequestInterface
    {
        if (empty($_SESSION['userid']) && isset($_COOKIE['auth'])) {
            $this->attemptAutoLogin(new HiddenString($_COOKIE['auth']));
        }
        return $request;
    }
}