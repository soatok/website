<?php
declare(strict_types=1);
namespace Soatok\Website\Engine;

use FastRoute\Dispatcher;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use ParagonIE\Ionizer\InvalidDataException;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    RoutingException,
    SecurityException
};

/**
 * Class Router
 * @package Soatok\Website\Engine
 */
class Router
{
    /** @var Dispatcher $dispatcher */
    private $dispatcher;

    /**
     * Router constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return array
     *
     * @todo Integrate with Anti-CSRF, CSP-Builder, etc.
     */
    public function getResponseHeaders(): array
    {
        return [];
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws BaseException
     * @throws RoutingException
     * @throws SecurityException
     */
    public function getResponse(RequestInterface $request): ResponseInterface
    {
        try {
            $path = $this->normalizeRequestPath(
                $request->getRequestTarget()
            );
        } catch (RoutingException $ex) {
            $path = '';
            // Suppress
        }
        if ($this->isStaticResource($path)) {
            return $this->serveStaticResource($path);
        }

        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getRequestTarget()
        );

        if ($routeInfo[0] !== Dispatcher::FOUND) {
            throw new RoutingException('HTTP/2 404 File Not Found', 404);
        }

        /**
         * @var string $handlerClass
         * @var array<int, string|int> $vars
         */
        $handlerClass = $routeInfo[1];
        $vars = $routeInfo[2];

        if (\is_object($handlerClass)) {
            if (!($handlerClass instanceof RequestHandlerInterface)) {
                throw new \TypeError(
                    'Handler is not an instance of RequestHandlerInterface'
                );
            }
            $handler = $handlerClass;
        } else {
            $handler = new $handlerClass($request);
        }

        if (!($handler instanceof RequestHandlerInterface)) {
            throw new \TypeError(
                'Handler is not an instance of RequestHandlerInterface'
            );
        }
        $handler->setVars($vars);
        $request = $this->filterInput($request, $handler);
        foreach ($handler->getMiddleware() as $mw) {
            if ($mw instanceof MiddlewareInterface) {
                $request = $mw($request);
            }
        }
        return $handler($request);
    }

    /**
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return RequestInterface
     */
    public function filterInput(
        RequestInterface $request,
        RequestHandlerInterface $handler
    ): RequestInterface {
        $requestBody = (string) $request->getBody();
        if (empty($requestBody)) {
            return $request->withBody(stream_for(''));
        }
        $post = [];
        \parse_str($requestBody, $post);

        try {
            $fc = $handler->getInputFilterContainer();
            return $request->withBody(
                stream_for(
                    \http_build_query(
                        $fc($post)
                    )
                )
            );
        } catch (InvalidDataException $ex) {
            return $request->withBody(stream_for(''));
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isStaticResource(string $path): bool
    {
        if ($path === SOATOK_WEB_ROOT) {
            return false;
        }
        if (!\file_exists($path)) {
            return false;
        }
        if (!\is_readable($path)) {
            return false;
        }
        $pos = \strpos($path, SOATOK_WEB_ROOT);
        if ($pos !== 0) {
            // Directory traversal attempt
            false;
        }
        return true;
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws RoutingException
     */
    public function normalizeRequestPath(string $path): string
    {
        $path = \realpath(SOATOK_WEB_ROOT . '/' . \trim($path, '/'));
        if (!\is_string($path)) {
            throw new RoutingException();
        }
        return $path;
    }

    /**
     * @param RequestInterface $request
     *
     * @return void
     *
     * @throws BaseException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function serve(RequestInterface $request)
    {
        try {
            $response = $this->getResponse($request);
        } catch (RoutingException $ex) {
            $response = GlobalConfig::instance()
                ->getTemplates()
                ->render('error404.twig', [], 404);
        }

        $this->output($response);
    }

    /**
     * @param string $path
     *
     * @return ResponseInterface
     * @throws BaseException
     * @throws SecurityException
     */
    public function serveStaticResource(string $path): ResponseInterface
    {
        if (!$this->isStaticResource($path)) {
            throw new SecurityException('Attempted file disclosure');
        }
        $headers = [
            'Cache-Control' => 'max-age=2592000, public',
            'Content-Type' => Utility::mimeType($path)
        ];

        return new Response(
            200,
            $headers,
            \file_get_contents($path),
            GlobalConfig::instance()->getHttpVersion()
        );
    }

    /**
     * @param ResponseInterface $response
     * @return void
     */
    public function output(ResponseInterface $response)
    {
        // Standard response headers:
        foreach ($response->getHeaders() as $key => $headers) {
            foreach ($headers as $value) {
                \header($key . ': ' . $value, false);
            }
        }

        // Handler-defined response headers:
        foreach ($this->getResponseHeaders() as $key => $headers) {
            foreach ($headers as $value) {
                \header($key . ': ' . $value, false);
            }
        }
        echo (string) $response->getBody();
        exit(0);
    }
}
