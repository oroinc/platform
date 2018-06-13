<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Extractor;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor as BaseExtractor;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetectorAwareInterface;
use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
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

    /** @var string */
    protected $cacheFile;

    /** @var bool */
    protected $debug;

    /** @var Route[]|null */
    protected $routes;

    /**
     * @param ContainerInterface   $container
     * @param RouterInterface      $router
     * @param Reader               $reader
     * @param DocCommentExtractor  $commentExtractor
     * @param ControllerNameParser $controllerNameParser
     * @param array                $handlers
     * @param array                $annotationsProviders
     * @param string               $cacheFile
     * @param bool|false           $debug
     */
    public function __construct(
        ContainerInterface $container,
        RouterInterface $router,
        Reader $reader,
        DocCommentExtractor $commentExtractor,
        ControllerNameParser $controllerNameParser,
        array $handlers,
        array $annotationsProviders,
        $cacheFile,
        $debug = false
    ) {
        parent::__construct(
            $container,
            $router,
            $reader,
            $commentExtractor,
            $controllerNameParser,
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
        /**
         * disabling the garbage collector gives a significant performance gain (about 2 times)
         * because a lot of config and metadata objects with short lifetime are used
         * this happens because we work with clones of these objects
         * @see \Oro\Bundle\ApiBundle\Provider\ConfigProvider::getConfig
         * @see \Oro\Bundle\ApiBundle\Provider\RelationConfigProvider::getRelationConfig
         * @see \Oro\Bundle\ApiBundle\Provider\MetadataProvider::getMetadata
         */
        gc_disable();
        $result = parent::all($this->resolveView($view));
        gc_enable();

        return $result;
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
     *
     * @param string $view
     */
    public function warmUp($view = ApiDoc::DEFAULT_VIEW)
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
     *
     * @param string $view
     */
    public function clear($view = ApiDoc::DEFAULT_VIEW)
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
