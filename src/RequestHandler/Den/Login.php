<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use GuzzleHttp\Psr7\Response;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\InputFilterContainer;
use ParagonIE\Ionizer\InvalidDataException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\Exceptions\NoSuchUserException;
use Soatok\Website\Engine\Exceptions\SecurityException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Utility;
use Soatok\Website\FilterRules\Den\LoginFilter;
use Soatok\Website\Struct\User;

/**
 * Class Login
 * @package Soatok\Website\RequestHandler\Den
 */
class Login implements RequestHandlerInterface
{
    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new LoginFilter();
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
     * @param array $params
     * @return Response
     *
     * @throws BaseException
     * @throws SecurityException
     * @throws \SodiumException
     */
    protected function login(array $params): Response
    {
        if (!isset($params['username'], $params['passphrase'])) {
            throw new SecurityException('Form not completed.');
        }
        $username = $params['username'];
        $passphrase = new HiddenString($params['passphrase']);

        $user = User::byUsername($username);
        if ($user->checkPassword($passphrase)) {
            $_SESSION['userid'] = $user->getId();
        } else {
            throw new NoSuchUserException('Invalid username and/or passphrase');
        }

        return Utility::redirect('/den');
    }

    /**
     * Process an HTTP request, produce an HTTP response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws InvalidDataException
     * @throws BaseException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $post = Utility::getParams($request);
        $twigVars = [];
        if ($post) {
            try {
                return $this->login($post);
            } catch (\Throwable $ex) {
                if (GlobalConfig::instance()->isDebug()) {
                    $twigVars['error'] = \get_class($ex) . ': ' .
                         $ex->getMessage() . PHP_EOL .
                         $ex->getFile() . PHP_EOL .
                         'Line ' . $ex->getLine() . PHP_EOL .
                         '<pre>'. $ex->getTraceAsString() . '</pre>';
                } else {
                    $twigVars['error'] = $ex->getMessage();
                }
            }
        }

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/login.twig', $twigVars);

    }
}
