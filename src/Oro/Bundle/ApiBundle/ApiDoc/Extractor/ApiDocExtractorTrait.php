<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\ApiDocAnnotationHandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Component\Routing\RouteCollectionUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Common functionality for ApiDocExtractor and CachingApiDocExtractor.
 */
trait ApiDocExtractorTrait
{
    private RouteOptionsResolverInterface $routeOptionsResolver;
    private RestDocViewDetector $docViewDetector;
    private ApiDocAnnotationHandlerInterface $apiDocAnnotationHandler;

    public function setRouteOptionsResolver(RouteOptionsResolverInterface $routeOptionsResolver): void
    {
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    public function setRestDocViewDetector(RestDocViewDetector $docViewDetector): void
    {
        $this->docViewDetector = $docViewDetector;
    }

    public function setApiDocAnnotationHandler(ApiDocAnnotationHandlerInterface $apiDocAnnotationHandler): void
    {
        $this->apiDocAnnotationHandler = $apiDocAnnotationHandler;
    }

    /**
     * @param Route[] $routes
     *
     * @return Route[]
     */
    private function processRoutes(array $routes): array
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

    private function resolveView(string $view): string
    {
        $detectedView = $this->docViewDetector->getView();
        if ($detectedView) {
            $view = $detectedView;
        }

        return $view;
    }

    /**
     * This is optimized by performance and customized version of Nelmio's "extractAnnotations" method v2.13.0
     * @see \Nelmio\ApiDocBundle\Extractor\ApiDocExtractor::extractAnnotations
     *
     * @param Route[]  $routes
     * @param string   $view
     * @param string[] $excludeSections
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function doExtractAnnotations(array $routes, string $view, array $excludeSections): array
    {
        $array = [];
        $resources = [];

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(sprintf(
                    'All elements of $routes must be instances of Route. "%s" given',
                    get_debug_type($route)
                ));
            }

            $method = $this->getReflectionMethod($route->getDefault('_controller'));
            if ($method) {
                /** @var ApiDoc $annotation */
                $annotation = $this->reader->getMethodAnnotation($method, static::ANNOTATION_CLASS);
                if (null !== $annotation) {
                    $this->apiDocAnnotationHandler->handle($annotation, $route);
                    if ($this->isApplicableForView($annotation, $view)
                        && !\in_array($annotation->getSection(), $excludeSections, true)
                    ) {
                        $element = ['annotation' => $this->extractData($annotation, $route, $method)];
                        $resource = $this->getRouteResource($annotation, $route);
                        if ($resource) {
                            $element['resource'] = $resource;
                            $resources[] = $resource;
                        }
                        $action = $this->getRouteAction($route);
                        if ($action) {
                            $element['action'] = $action;
                        }
                        $array[] = $element;
                    }
                }
            }
        }

        foreach ($this->annotationsProviders as $annotationProvider) {
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

    private function doAddResources(array &$array, array $resources): void
    {
        rsort($resources);
        foreach ($array as $index => $element) {
            if (\array_key_exists('resource', $element)) {
                continue;
            }

            $hasResource = false;
            $path = $element['annotation']->getRoute()->getPath();

            foreach ($resources as $resource) {
                if (str_starts_with($path, $resource) || $resource === $element['annotation']->getResource()) {
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

    private function doSortAnnotations(array &$array): void
    {
        $methodOrder = [
            Request::METHOD_HEAD    => 1,
            Request::METHOD_OPTIONS => 2,
            Request::METHOD_GET     => 3,
            Request::METHOD_POST    => 4,
            Request::METHOD_PUT     => 5,
            Request::METHOD_PATCH   => 6,
            Request::METHOD_DELETE  => 7
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

    private function getRouteResource(ApiDoc $annotation, Route $route): ?string
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

    private function getRouteMethodOrder(Route $route, array $methodOrder): int
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

    private function getRouteAction(Route $route): ?string
    {
        return $route->getDefault('_action');
    }

    private function isApplicableForView(ApiDoc $annotation, string $view): bool
    {
        $views = $annotation->getViews();
        if (!$views) {
            return ApiDoc::DEFAULT_VIEW === $view;
        }
        if (\count($views) === 1 && reset($views) === 'all') {
            return true;
        }

        return \in_array($view, $views, true);
    }
}
