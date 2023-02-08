<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Extractor;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor as BaseExtractor;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetectorAwareInterface;
use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * The optimized and adapted version of Nelmio CachingApiDocExtractor.
 */
class CachingApiDocExtractor extends BaseExtractor implements
    RouteOptionsResolverAwareInterface,
    RestDocViewDetectorAwareInterface
{
    use ApiDocExtractorTrait;

    private string $cacheFile;
    private bool $debug;
    /** @var Route[]|null */
    private ?array $routes = null;

    public function __construct(
        ContainerInterface $container,
        RouterInterface $router,
        Reader $reader,
        DocCommentExtractor $commentExtractor,
        array $handlers,
        array $annotationsProviders,
        string $cacheFile,
        bool $debug = false
    ) {
        parent::__construct(
            $container,
            $router,
            $reader,
            $commentExtractor,
            $handlers,
            $annotationsProviders,
            $cacheFile,
            $debug
        );

        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        if (null === $this->routes) {
            $this->routes = $this->processRoutes(parent::getRoutes());
        }

        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        return parent::all($this->resolveView($view));
    }

    /**
     * {@inheritdoc}
     */
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        return $this->doExtractAnnotations(
            $routes,
            $view,
            $this->container->getParameter('nelmio_api_doc.exclude_sections')
        );
    }

    /**
     * Warms up the API documentation cache.
     */
    public function warmUp(string $view = ApiDoc::DEFAULT_VIEW): void
    {
        $this->clear($view);

        $previousView = $this->docViewDetector->getView();
        try {
            $this->docViewDetector->setView($view);
            // This method can be used several times for warming up a cache for different views.
            // So, to avoid collisions, we need to clone routes because they may be changed by option resolvers.
            $clonedRoutes = [];
            $routes = parent::getRoutes();
            foreach ($routes as $name => $route) {
                $clonedRoutes[$name] = clone $route;
            }
            $this->routes = $this->processRoutes($clonedRoutes);
            $this->all($view);
        } finally {
            // restore a previous view
            $this->docViewDetector->setView('' === $previousView ? null : $previousView);
        }
    }

    /**
     * Clears the API documentation cache.
     */
    public function clear(string $view = ApiDoc::DEFAULT_VIEW): void
    {
        $cacheFilePath = $this->cacheFile . '.' . $view;
        if (is_file($cacheFilePath)) {
            unlink($cacheFilePath);
        }
        if ($this->debug) {
            $cacheMetaFilePath = $cacheFilePath . '.meta';
            if (is_file($cacheMetaFilePath)) {
                unlink($cacheMetaFilePath);
            }
        }
    }
}
