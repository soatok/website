<?php
declare(strict_types=1);
namespace Soatok\Website\Engine;

use function FastRoute\cachedDispatcher;
use League\CommonMark\CommonMarkConverter;
use ParagonIE\GPGMailer\GPGMailer;
use ParagonIE\GPGMailer\GPGMailerException;
use Soatok\DholeCrypto\Contract\CryptographicKeyInterface;
use Soatok\DholeCrypto\Key\{
    AsymmetricPublicKey,
    AsymmetricSecretKey,
    SymmetricKey
};
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    CryptoException,
    FileNotFoundException,
    FileReadException,
    JSONException
};
use ParagonIE\EasyDB\{
    EasyDB,
    Factory
};
use Zend\Mail\Transport\{
    Sendmail,
    Smtp,
    SmtpOptions,
    TransportInterface
};

/**
 * Class GlobalConfig
 * @package Soatok\Website
 */
final class GlobalConfig
{
    const DEFAULT_TWIG_HOSTNAME = 'soatok.com';

    /** @var CommonMarkConverter $commonMark */
    private $commonMark;

    /** @var string $configDir */
    private $configDir = '';

    /** @var EasyDB $db */
    private $db;

    /** @var \HTMLPurifier $purifier */
    private $purifier;

    /** @var Router $router */
    private $router;

    /** @var array $settings */
    private $settings;

    /**
     * @var GlobalConfig $instance
     */
    private static $instance;

    /**
     * GlobalConfig constructor.
     *
     * @throws FileNotFoundException
     * @throws FileReadException
     * @throws JSONException
     */
    private function __construct()
    {
        $path = SOATOK_ROOT . '/data';
        if (\is_dir($path . '/local')) {
            // Use the local configuration if it exists.
            $path .= '/local';
        }

        $this->configDir = $path;
        $this->settings = Utility::getJsonFile($path . '/settings.json');
        $this->db = Factory::create(
            $this->settings['database']['dsn']
                ?? 'sqlite:' . $path . '/db.sqlite',
            $this->settings['database']['user']
                ?? '',
            $this->settings['database']['pass'] ??
                '',
            $this->settings['database']['options']
                ?? []
        );
    }

    /**
     * @return self
     * @throws BaseException
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    public function getConfigDirectory(): string
    {
        return $this->configDir;
    }

    /**
     * @return EasyDB
     */
    public function getDatabase(): EasyDB
    {
        return $this->db;
    }

    /**
     * @param TransportInterface|null $transport
     *
     * @return GPGMailer
     * @throws GPGMailerException
     */
    public function getGpgMailer(?TransportInterface $transport = null): GPGMailer
    {
        if (\is_null($transport)) {
            $transport = $this->getMailTransport();
        }
        /** @var TransportInterface $transport */
        if (\is_readable($this->configDir . '/private.key')) {
            return new GPGMailer(
                $transport,
                $this->settings['gpg-mailer'] ?? [],
                \file_get_contents($this->configDir . '/private.key')
            );
        }
        return new GPGMailer(
            $transport,
            $this->settings['gpg-mailer'] ?? []
        );
    }

    /**
     * @return string
     */
    public function getHttpVersion(): string
    {
        return '1.1';
    }

    /**
     * @return \HTMLPurifier
     */
    public function getHtmlPurifier(): \HTMLPurifier
    {
        if (!$this->purifier) {
            $config = \HTMLPurifier_Config::createDefault();

            $config->set(
                'Cache.SerializerPath',
                SOATOK_ROOT . '/data/purified/'
            );

            $this->purifier = new \HTMLPurifier($config);
        }
        return $this->purifier;
    }


    /**
     * @param bool $forceLoad
     *
     * @return array<string, CryptographicKeyInterface>
     * @throws CryptoException
     * @throws FileNotFoundException
     */
    private function getKeyring(bool $forceLoad = false): array
    {
        if (!empty($this->keyring) && !$forceLoad) {
            return $this->keyring;
        }
        if (!\is_readable($this->configDir . '/keys.php')) {
            throw new FileNotFoundException(
                'Cannot open ' . $this->configDir . '/keys.php'
            );
        }

        $keyring = include $this->configDir . '/keys.php';
        if (!isset($keyring['secret-key'], $keyring['public-key'], $keyring['shared-key'])) {
            throw new CryptoException('Mandatory keys are not defined in keyring');
        }
        foreach ($keyring as $key) {
            if (!($key instanceof CryptographicKeyInterface)) {
                throw new \TypeError();
            }
        }

        if (!($keyring['secret-key'] instanceof AsymmetricSecretKey)) {
            throw new \TypeError();
        }
        if (!($keyring['public-key'] instanceof AsymmetricPublicKey)) {
            throw new \TypeError();
        }
        if (!($keyring['shared-key'] instanceof SymmetricKey)) {
            throw new \TypeError();
        }
        $this->keyring = $keyring;
        return $this->keyring;
    }

    /**
     * @return TransportInterface
     */
    public function getMailTransport(): TransportInterface
    {
        if (empty($this->settings['mail-transport']['type'])) {
            return new Sendmail();
        }
        switch ($this->settings['mail-transport']['type']) {
            case 'sendmail':
                return new Sendmail();
            case 'smtp':
                $options = new SmtpOptions(
                    $this->settings['mail-transport']['options']
                );
                return new Smtp($options);
            default:
                throw new \TypeError('Invalid mail transport type');
        }
    }

    /**
     * @return CommonMarkConverter
     */
    public function getMarkdownRenderer(): CommonMarkConverter
    {
        if (!$this->commonMark) {
            $this->commonMark = new CommonMarkConverter();
        }
        return $this->commonMark;
    }

    /**
     * @return Router
     * @throws FileReadException
     */
    public function getRouter(): Router
    {
        if (!$this->router) {
            $routes = require_once $this->configDir . '/routes.php';
            if ( ! \is_callable($routes)) {
                throw new FileReadException('Cannot read routes.php');
            }
            if ( ! \array_key_exists('twig-cache', $this->settings)) {
                $cacheDisabled = true;
            } else {
                $cacheDisabled = ! $this->settings['cache'];
            }

            $dispatcher = cachedDispatcher(
                $routes,
                [
                    'cacheFile'     => $this->configDir . '/route.cache',
                    'cacheDisabled' => $cacheDisabled,
                ]
            );

            $this->router = new Router($dispatcher);
        }
        return $this->router;
    }

    /**
     * @return AsymmetricPublicKey
     * @throws CryptoException
     * @throws FileNotFoundException
     */
    public function getPublicKey(): AsymmetricPublicKey
    {
        $keyring = $this->getKeyring();
        /** @var AsymmetricPublicKey $publicKey */
        $publicKey = $keyring['public-key'];
        if (!($keyring['public-key'] instanceof AsymmetricPublicKey)) {
            throw new \TypeError();
        }
        return $publicKey;
    }

    /**
     * @return AsymmetricSecretKey
     * @throws CryptoException
     * @throws FileNotFoundException
     */
    public function getSecretKey(): AsymmetricSecretKey
    {
        $keyring = $this->getKeyring();
        /** @var AsymmetricSecretKey $secretKey */
        $secretKey = $keyring['secret-key'];
        if (!($keyring['secret-key'] instanceof AsymmetricSecretKey)) {
            throw new \TypeError();
        }
        return $secretKey;
    }

    /**
     * @return array<string, int|bool|string>
     */
    public function getSessionConfig(): array
    {
        if (!isset($this->settings['session'])) {
            return [
                'cookie_httponly' => true
            ];
        }
        return $this->settings['session'];
    }

    /**
     * @return SymmetricKey
     * @throws CryptoException
     * @throws FileNotFoundException
     */
    public function getSymmetricKey(): SymmetricKey
    {
        $keyring = $this->getKeyring();
        /** @var SymmetricKey $sharedKey */
        $sharedKey = $keyring['shared-key'];
        if (!($keyring['shared-key'] instanceof SymmetricKey)) {
            throw new \TypeError();
        }
        return $sharedKey;
    }

    /**
     * @param string $subdir
     * @return \Twig_Environment
     */
    public function getTwig(
        string $subdir = self::DEFAULT_TWIG_HOSTNAME
    ): \Twig_Environment {
        $twig_loader = new \Twig_Loader_Filesystem([
            SOATOK_ROOT . '/templates/' . $subdir,
            SOATOK_ROOT . '/templates/common'
        ]);
        $twig_env = new \Twig_Environment($twig_loader);

        /** @var array<string, array<string, callable>> $filters */
        $custom = require $this->configDir . '/twig.php';

        foreach ($custom['functions'] as $name => $callable) {
            $twig_env->addFunction(
                new \Twig_Function(
                    $name,
                    $callable,
                    ['is_safe' => ['html']]
                )
            );
        }
        foreach ($custom['filters'] as $name => $callable) {
            $twig_env->addFilter(new \Twig_Filter($name, $callable));
        }
        foreach ($custom['globals'] as $name => $value) {
            $twig_env->addGlobal($name, $value);
        }
        $twig_env->addGlobal('get', $_GET);
        $twig_env->addGlobal('post', $_POST);
        $twig_env->addGlobal('session', $_SESSION);

        return $twig_env;
    }

    /**
     * Get a TemplateRenderer for a specific namespace (i.e. subdirectory
     * of the templates folder)
     *
     * @param string $subdir
     * @return TemplateRenderer
     */
    public function getTemplates(
        string $subdir = self::DEFAULT_TWIG_HOSTNAME
    ): TemplateRenderer {
        return new TemplateRenderer($this->getTwig($subdir));
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        if (isset($this->settings['debug'])) {
            return true;
        }
        return false;
    }
}
