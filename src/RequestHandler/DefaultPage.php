<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler;

use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Traits\RequestHandlerTrait;
use Soatok\Website\FilterRules\VoidFilter;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class DefaultPage
 * @package Soatok\Website\RequestHandler
 */
class DefaultPage implements RequestHandlerInterface
{
    use RequestHandlerTrait;

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
        return $this;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws BaseException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        return GlobalConfig::instance()
           ->getTemplates()
           ->render('index.twig');
    }
}
