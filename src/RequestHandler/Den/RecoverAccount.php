<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler\Den;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Ionizer\{
    InputFilterContainer,
    InvalidDataException
};
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    NoSuchUserException
};
use Soatok\Website\Engine\{
    GlobalConfig,
    Utility
};
use Soatok\Website\FilterRules\Den\AccountRecoveryFilter;
use Soatok\Website\Struct\User;
use Zend\Mail\{
    Message,
    Transport\Sendmail
};

/**
 * Class RecoverAccount
 * @package Soatok\Website\RequestHandler\Den
 */
class RecoverAccount implements RequestHandlerInterface
{
    const FROM = 'no-reply@soatok.com';

    /** @var string $recoveryToken */
    protected $recoveryToken;

    /**
     * @return InputFilterContainer
     */
    public function getInputFilterContainer(): InputFilterContainer
    {
        return new AccountRecoveryFilter();
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
        if (!empty($vars['token'])) {
            $this->recoveryToken = $vars['token'];
        }
        return $this;
    }

    /**
     * @return ResponseInterface
     * @throws BaseException
     * @throws \SodiumException
     */
    protected function processToken(): ResponseInterface
    {
        try {
            $user = User::byRecoveryToken(
                new HiddenString($this->recoveryToken)
            );
        } catch (NoSuchUserException $ex) {
            return Utility::redirect('/den/login');
        }

        \session_regenerate_id(true);
        $_SESSION['userid'] = $user->getId();
        $_SESSION['logout-nonce'] = Base64UrlSafe::encode(\random_bytes(33));

        return Utility::redirect('/den');
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array<string, mixed>
     * @throws BaseException
     * @throws \PEAR_Exception
     * @throws \SodiumException
     */
    protected function sendRecoveryToken(array $post): array
    {
        $success = [
            'success' =>
                'Please check your email for a recovery link.'
        ];
        if (!isset($post['username'])) {
            return ['error' => 'Please specify a username'];
        }
        try {
            $user = User::byUsername($post['username']);
        } catch (NoSuchUserException $ex) {
            // I LIED! meme goes here
            return $success;
        }
        $token = $user->createRecoveryToken();
        $to = $user->getEmail();
        $message = (new Message())
            ->setFrom(self::FROM)
            ->setTo($to)
            ->setSubject('Dreamseeker Den Account Recovery')
            ->setBody(
                "Please go to this URL in your browser to recover your " .
                "Dreamseeker Den account: \n\n" .
                "https://soatok.com/den/account-recovery/" . $token->getString() . "\n"
            );

        $transport = new Sendmail();

        $fingerprint = $user->getGPGFingerprint();
        if ($fingerprint) {
            // Encrypt the email before sending it:
            GlobalConfig::instance()
                ->getGpgMailer($transport)
                ->send($message, $fingerprint);
        } else {
            // Send plaintext because the user didn't give their public key:
            $transport->send($message);
        }
        return $success;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws BaseException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws InvalidDataException
     * @throws \PEAR_Exception
     * @throws \SodiumException
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if ($this->recoveryToken) {
            return $this->processToken();
        }
        $post = Utility::getParams($request);
        $twigVars = [];
        if ($post) {
            $twigVars = $this->sendRecoveryToken($post);
        }

        return GlobalConfig::instance()
            ->getTemplates()
            ->render('den/recover-account.twig', $twigVars);
    }
}
