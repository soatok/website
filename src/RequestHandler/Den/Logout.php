<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\Utility;
use Soatok\Website\FilterRules\VoidFilter;

/**
 * Class Logout
 * @package Soatok\Website\RequestHandler\Den
 */
class Logout implements RequestHandlerInterface
{
    private $nonce;

    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new VoidFilter();
    }

    /**
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddleware(): array
    {
        return [];
    }

    /**
     * @param array $vars
     * @return RequestHandlerInterface
     */
    public function setVars(array $vars): RequestHandlerInterface
    {
        $this->nonce = (string) \array_shift($vars);
        return $this;
    }

    /**
     * Process an HTTP request, produce an HTTP response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws BaseException
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if (!isset($_SESSION['userid'])) {
            return Utility::redirect('/den/login');
        }
        if (!isset($_SESSION['logout-nonce'])) {
            $_SESSION['logout-nonce'] = Base64UrlSafe::encode(\random_bytes(33));
        }
        if (\hash_equals($_SESSION['logout-nonce'], $this->nonce)) {
            unset($_SESSION['userid']);
            \session_regenerate_id(true);
        }
        if (isset($_COOKIE['auth'])) {
            Utility::setCookie('auth', null, \time() - 86400);
        }

        return Utility::redirect('/den/login');
    }
}
