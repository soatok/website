<?php
namespace Soatok\Website\Engine;

use Soatok\Website\Engine\Exceptions\BaseException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TemplateRenderer
 * @package Soatok\Website\Engine
 */
class TemplateRenderer
{
    /** @var \Twig_Environment $env */
    private $env;

    /**
     * TemplateRenderer constructor.
     *
     * @param \Twig_Environment $env
     */
    public function __construct(\Twig_Environment $env)
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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
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
