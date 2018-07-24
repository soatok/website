<?php
declare(strict_types=1);
namespace Soatok\Website\Engine;

use GuzzleHttp\Psr7\Response;
use ParagonIE\Ionizer\InputFilterContainer;
use ParagonIE\Ionizer\InvalidDataException;
use Psr\Http\Message\RequestInterface;
use Soatok\Website\Engine\Exceptions\{
    BaseException, FileNotFoundException, FileReadException, JSONException
};

/**
 * Class Utility
 * @package Soatok\Website\Engine
 */
abstract class Utility
{
    /**
     * Load a JSON file's contents, return an array.
     *
     * @param string $path
     * @return array
     *
     * @throws FileNotFoundException
     * @throws FileReadException
     * @throws JSONException
     */
    public static function getJsonFile(string $path): array
    {
        if (!\is_readable($path)) {
            throw new FileNotFoundException(
                'Configuration file not found: ' . $path
            );
        }
        /** @var string|bool $raw */
        $raw = \file_get_contents($path);
        if (!\is_string($raw)) {
            throw new FileReadException(
                'Could not read configuration file: ' . $path
            );
        }

        /** @var array|null $decoded */
        $decoded = \json_decode($raw, true);
        if (!\is_array($decoded)) {
            throw new JSONException(
                \json_last_error_msg(),
                \json_last_error()
            );
        }
        return $decoded;
    }

    /**
     * @param string $path
     * @param bool $skipCache
     *
     * @return string
     * @throws BaseException
     * @throws FileNotFoundException
     * @throws FileReadException
     */
    public static function getMarkdownFile(
        string $path,
        bool $skipCache = false
    ): string {
        if (!\is_readable($path)) {
            throw new FileNotFoundException(
                'Markdown file not found: ' . $path
            );
        }
        if (\is_readable($path . '.cache') && !$skipCache) {
            return (string) \file_get_contents($path . '.cache');
        }

        /** @var string|bool $raw */
        $input = \file_get_contents($path);
        if (!\is_string($input)) {
            throw new FileReadException(
                'Could not read Markdown file: ' . $path
            );
        }

        $config = GlobalConfig::instance();
        $html = $config->getHtmlPurifier()->purify(
            $config->getMarkdownRenderer()->convertToHtml($input)
        );
        \file_put_contents($path . '.cache', $html);
        return $html;
    }

    /**
     * @param string $class
     * @return string
     */
    public static function decorateClassName($class = ''): string
    {
        return 'Object (' . \trim($class, '\\') . ')';
    }

    /**
     * Get a variable's type. If it's an object, also get the class name.
     *
     * @param mixed $mixed
     * @return string
     */
    public static function getGenericType($mixed = null): string
    {
        if (\func_num_args() === 0) {
            return 'void';
        }
        if ($mixed === null) {
            return 'null';
        }
        if (\is_object($mixed)) {
            return static::decorateClassName(\get_class($mixed));
        }
        $type = \gettype($mixed);
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'integer':
                return 'int';
            default:
                return $type;
        }
    }

    /**
     * @param RequestInterface $request
     * @param null|InputFilterContainer $container
     *
     * @return array
     * @throws InvalidDataException
     */
    public static function getParams(
        RequestInterface $request,
        ?InputFilterContainer $container = null
    ): array {
        $params = [];
        \parse_str((string) $request->getBody(), $params);
        if ($container) {
            $params = $container($params);
        }
        return $params;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function mimeType(string $path): string
    {
        if (\preg_match('/\.css$/', $path)) {
            return 'text/css; charset=UTF-8';
        }
        if (\preg_match('/\.js$/', $path)) {
            return 'application/javascript; charset=UTF-8';
        }
        if (\is_callable('mime_content_type')) {
            $type = \mime_content_type($path);
            // TODO: Whitelist
        } else {
            $type = 'text/plain; charset=UTF-8';
        }
        return $type;
    }

    /**
     * @param string $path
     * @param int $code
     *
     * @return Response
     * @throws BaseException
     */
    public static function redirect(string $path, int $code = 301): Response
    {
        return new Response(
            $code,
            ['Location' => $path],
            '',
            GlobalConfig::instance()->getHttpVersion()
        );
    }
}
