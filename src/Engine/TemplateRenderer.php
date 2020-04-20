<?php
namespace Soatok\Website\Engine;

use Soatok\Website\Engine\Exceptions\BaseException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class TemplateRenderer
 * @package Soatok\Website\Engine
 */
class TemplateRenderer
{
    /** @var Environment $env */
    private $env;

    /**
     * TemplateRenderer constructor.
     *
     * @param Environment $env
     */
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * @param string $template
     * @param array $context
     * @param int $statusCode
     * @param array $headers
     *
     * @return ResponseInterface
     *
     * @throws BaseException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(
        string $template,
        array $context = [],
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        return new Response(
            $statusCode,
            $headers,
            $this->env->render($template, $context),
            GlobalConfig::instance()->getHttpVersion()
        );
    }
}
