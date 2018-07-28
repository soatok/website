<?php
namespace Soatok\Website\Engine\Contract;

use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

/**
 * Interface RequestHandlerInterface
 * @package Soatok\Website\Contract
 */
interface RequestHandlerInterface
{
    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer;

    /**
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddleware(): array;

    /**
     * @return array<string, mixed>
     */
    public function getPostData(): array;

    /**
     * @param array $post
     * @return void
     */
    public function setPostData(array $post);

    /**
     * @param array $vars
     * @return RequestHandlerInterface
     */
    public function setVars(array $vars): RequestHandlerInterface;

    /**
     * Process an HTTP request, produce an HTTP response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request): ResponseInterface;
}
