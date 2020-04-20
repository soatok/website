<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler;

use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Traits\RequestHandlerTrait;
use Soatok\Website\FilterRules\VoidFilter;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class Projects
 * @package Soatok\Website\RequestHandler
 */
class Projects implements RequestHandlerInterface
{
    use RequestHandlerTrait;

    /** @var string $project */
    protected $project = '';

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
        if (empty($vars['name'])) {
            return $this;
        }
        $name = $vars['name'];
        if ($this->pageExistsInDirectory($name)) {
            $this->project = $name;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $tplDir
     *
     * @return bool
     */
    protected function pageExistsInDirectory(
        string $name,
        string $tplDir = GlobalConfig::DEFAULT_TWIG_HOSTNAME
    ): bool {
        $dir = SOATOK_ROOT . '/templates/' . $tplDir . '/projects';

        $file = $dir . '/' . $name . '.twig';
        if (\file_exists($file)) {
            $file = \realpath($file);
            if (\strpos($file, $dir) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws BaseException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if (empty($this->project)) {
            return GlobalConfig::instance()
                ->getTemplates()
                ->render('projects.twig');
        }
        return GlobalConfig::instance()
            ->getTemplates()
            ->render('projects/' . $this->project . '.twig');
    }
}
