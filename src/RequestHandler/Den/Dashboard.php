<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use ParagonIE\ConstantTime\Base64UrlSafe;
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
use Soatok\Website\Middleware\{
    AutoLoginMiddleware,
    DenMiddleware
};
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class Dashboard
 * @package Soatok\Website\RequestHandler
 */
class Dashboard implements RequestHandlerInterface
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
        if (!isset($_SESSION['logout-nonce'])) {
            $_SESSION['logout-nonce'] = Base64UrlSafe::encode(\random_bytes(33));
        }
        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/index.twig');
    }
}
