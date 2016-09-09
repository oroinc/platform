<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Component\Routing\RouteCollectionUtil;

trait ApiDocExtractorTrait
{
    /** @var RouteOptionsResolverInterface */
    protected $routeOptionsResolver;

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /**
     * Sets the RouteOptionsResolver.
     *
     * @param RouteOptionsResolverInterface $routeOptionsResolver
     */
    public function setRouteOptionsResolver(RouteOptionsResolverInterface $routeOptionsResolver)
    {
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    /**
     * Sets the RestDocViewDetector.
     *
     * @param RestDocViewDetector $docViewDetector
     */
    public function setRestDocViewDetector(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * @param Route[] $routes
     *
     * @return Route[]
     */
    protected function processRoutes(array $routes)
    {
        $routeCollection = new EnhancedRouteCollection($routes);
        $routeCollectionAccessor = new RouteCollectionAccessor($routeCollection);
        /** @var Route $route */
        foreach ($routeCollection as $route) {
            $this->routeOptionsResolver->resolve($route, $routeCollectionAccessor);
        }
        $routes = $routeCollection->all();

        return RouteCollectionUtil::filterHidden($routes);
    }

    /**
     * @param string $view
     *
     * @return string
     */
    protected function resolveView($view)
    {
        $detectedView = $this->docViewDetector->getView();
        if ($detectedView) {
            $view = $detectedView;
        }

        return $view;
    }

    /**
     * This is optimized by performance and customized version of Nelmio's "extractAnnotations" method v2.13.0
     * @see Nelmio\ApiDocBundle\Extractor\ApiDocExtractor::extractAnnotations
     *
     * @param Route[]  $routes
     * @param string   $view
     * @param string[] $excludeSections
     *
     * @return array
     */
    protected function doExtractAnnotations(array $routes, $view, array $excludeSections)
    {
        $array = [];
        $resources = [];

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(
                    sprintf('All elements of $routes must be instances of Route. "%s" given', gettype($route))
                );
            }

            $method = $this->getReflectionMethod($route->getDefault('_controller'));
            if ($method) {
                /** @var ApiDoc $annotation */
                $annotation = $this->reader->getMethodAnnotation($method, static::ANNOTATION_CLASS);
                if ($annotation
                    && (
                        in_array($view, $annotation->getViews(), true)
                        || (0 === count($annotation->getViews()) && $view === ApiDoc::DEFAULT_VIEW)
                    )
                    && !in_array($annotation->getSection(), $excludeSections, true)
                ) {
                    $element = ['annotation' => $this->extractData($annotation, $route, $method)];
                    $resource = $this->getRouteResource($annotation, $route);
                    if ($resource) {
                        $element['resource'] = $resource;
                        $resources[] = $resource;
                    }
                    $array[] = $element;
                }
            }
        }

        foreach ($this->annotationsProviders as $annotationProvider) {
            /** @var ApiDoc[] $annotations */
            $annotations = $annotationProvider->getAnnotations();
            foreach ($annotations as $annotation) {
                $route = $annotation->getRoute();
                $element = [
                    'annotation' => $this->extractData(
                        $annotation,
                        $route,
                        $this->getReflectionMethod($route->getDefault('_controller'))
                    )
                ];
                $resource = $this->getRouteResource($annotation, $route);
                if ($resource) {
                    $element['resource'] = $resource;
                    $resources[] = $resource;
                }
                $array[] = $element;
            }
        }

        $this->doAddResources($array, $resources);
        $this->doSortAnnotations($array);

        return $array;
    }

    /**
     * @param array $array
     * @param array $resources
     */
    protected function doAddResources(array &$array, array $resources)
    {
        rsort($resources);
        foreach ($array as $index => $element) {
            if (array_key_exists('resource', $element)) {
                continue;
            }

            $hasResource = false;
            $path = $element['annotation']->getRoute()->getPath();

            foreach ($resources as $resource) {
                if (0 === strpos($path, $resource) || $resource === $element['annotation']->getResource()) {
                    $array[$index]['resource'] = $resource;

                    $hasResource = true;
                    break;
                }
            }

            if (false === $hasResource) {
                $array[$index]['resource'] = 'others';
            }
        }
    }

    /**
     * @param array $array
     */
    protected function doSortAnnotations(array &$array)
    {
        $methodOrder = [
            'HEAD'    => 1,
            'OPTIONS' => 2,
            'GET'     => 3,
            'POST'    => 4,
            'PUT'     => 5,
            'PATCH'   => 6,
            'DELETE'  => 7,
        ];
        usort(
            $array,
            function ($a, $b) use ($methodOrder) {
                $resourceA = $a['resource'];
                $resourceB = $b['resource'];
                if ($resourceA === $resourceB) {
                    /** @var Route $routeA */
                    $routeA = $a['annotation']->getRoute();
                    /** @var Route $routeB */
                    $routeB = $b['annotation']->getRoute();
                    if ($routeA->getPath() === $routeB->getPath()) {
                        $methodA = $this->getRouteMethodOrder($routeA, $methodOrder);
                        $methodB = $this->getRouteMethodOrder($routeB, $methodOrder);
                        if ($methodA === $methodB) {
                            return 0;
                        }

                        return $methodA > $methodB ? 1 : -1;
                    }

                    return strcmp($routeA->getPath(), $routeB->getPath());
                }

                return strcmp($resourceA, $resourceB);
            }
        );
    }

    /**
     * @param ApiDoc $annotation
     * @param Route  $route
     *
     * @return string|null
     */
    protected function getRouteResource(ApiDoc $annotation, Route $route)
    {
        if (!$annotation->isResource()) {
            return null;
        }

        $resource = $annotation->getResource();
        if (!$resource) {
            // remove format from routes used for resource grouping
            $resource = str_replace('.{_format}', '', $route->getPath());
        }

        return $resource;
    }

    /**
     * @param Route $route
     * @param array $methodOrder
     *
     * @return int
     */
    protected function getRouteMethodOrder(Route $route, $methodOrder)
    {
        $order = PHP_INT_MAX;
        $methods = $route->getMethods();
        foreach ($methods as $method) {
            if (isset($methodOrder[$method]) && $methodOrder[$method] < $order) {
                $order = $methodOrder[$method];
            }
        }

        return $order;
    }
}
