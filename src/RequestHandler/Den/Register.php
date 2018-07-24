<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

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
use Soatok\Website\FilterRules\Den\RegisterFilter;
use Soatok\Website\Struct\User;

/**
 * Class Register
 * @package Soatok\Website\RequestHandler\Den
 */
class Register implements RequestHandlerInterface
{
    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new RegisterFilter();
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
     * @param array $post
     *
     * @throws BaseException
     * @throws SecurityException
     * @throws \SodiumException
     * @return User
     */
    protected function createAccount(array $post): User
    {
        $db = GlobalConfig::instance()->getDatabase();
        if (User::usernameIsTaken($post['username'])) {
            throw new SecurityException(
                'User "' . $post['username'] . '" is already registered.'
            );
        }
        $user = new User($db);
        $user->set('username', $post['username'])
             ->set('email', $post['email']);
        if (!$user->create()) {
            throw new SecurityException('Could not create new user account.');
        }
        $user->setPassword(new HiddenString($post['passphrase']));
        $_SESSION['userid'] = $user->getId();
        return $user;
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
                $this->createAccount($post);
                return Utility::redirect('/den');
            } catch (\Throwable $ex) {
                $twigVars['error'] = $ex->getMessage();
            }
        }

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/register.twig', $twigVars);

    }
}
