<?php
declare(strict_types=1);
namespace Soatok\Website\Engine;

use function FastRoute\cachedDispatcher;
use League\CommonMark\CommonMarkConverter;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    FileNotFoundException,
    FileReadException,
    JSONException
};
use ParagonIE\EasyDB\{
    EasyDB,
    Factory
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
