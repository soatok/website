<?php
namespace Soatok\Website\Engine\Contract;

use Psr\Http\Message\RequestInterface;

/**
 * Interface MiddlewareInterface
 * @package Soatok\Website\Contract
 */
interface MiddlewareInterface
{
    /**
     * Middleware acts on requests before the handler.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function __invoke(RequestInterface $request): RequestInterface;
}
