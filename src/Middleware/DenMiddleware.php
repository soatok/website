<?php
declare(strict_types=1);
namespace Soatok\Website\Middleware;

use Psr\Http\Message\RequestInterface;
use Soatok\Website\Engine\Contract\MiddlewareInterface;

/**
 * Class DenMiddleware
 * @package Soatok\Website\Middleware
 */
class DenMiddleware implements MiddlewareInterface
{
    /**
     * Middleware acts on requests before the handler.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function __invoke(RequestInterface $request): RequestInterface
    {
        if (!isset($_SESSION['userid'])) {
            header('Location: /den/login');
            exit(1);
        }
        return $request;
    }
}
