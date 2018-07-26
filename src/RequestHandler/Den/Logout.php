<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\InputFilterContainer;
use ParagonIE\Ionizer\InvalidDataException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\Exceptions\SecurityException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Utility;
use Soatok\Website\FilterRules\VoidFilter;
use Soatok\Website\Struct\User;

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
        if (\hash_equals($_SESSION['logout-nonce'], $this->nonce)) {
            unset($_SESSION['userid']);
            \session_regenerate_id(true);
        }

        return Utility::redirect('/den/login');
    }
}
