<?php
declare(strict_types=1);
namespace Soatok\Website\RequestHandler;

use ParagonIE\Ionizer\InputFilterContainer;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Soatok\Website\Engine\Contract\RequestHandlerInterface;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Traits\RequestHandlerTrait;
use Soatok\Website\FilterRules\VoidFilter;

/**
 * Class StaticPage
 * @package Soatok\Website\RequestHandler
 */
class StaticPage implements RequestHandlerInterface
{
    use RequestHandlerTrait;

    /** @var string $page */
    protected $page = '';

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
        $name = $vars['name'];
        if ($this->pageExistsInDirectory($name)) {
            $this->page = $name;
        }
        if ($this->pageExistsInDirectory($name, 'common')) {
            $this->page = $name;
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
        $dir = SOATOK_ROOT . '/templates/' . $tplDir . '/pages';

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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        if (empty($this->page)) {
            return GlobalConfig::instance()
                ->getTemplates()
                ->render('error404.twig', [], 404);
        }
        return GlobalConfig::instance()
            ->getTemplates()
            ->render('pages/' . $this->page . '.twig');
    }
}
