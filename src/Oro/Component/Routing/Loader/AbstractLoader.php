<?php

namespace Oro\Component\Routing\Loader;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Abstract class used by all loaders which should work with EnhancedRouteCollection.
 */
abstract class AbstractLoader extends Loader
{
    /** @var RouteOptionsResolverInterface */
    protected $routeOptionsResolver;

    public function __construct(RouteOptionsResolverInterface $routeOptionsResolver)
    {
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    /**
     * @param mixed       $resource
     * @param string|null $type
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        $routes = new EnhancedRouteCollection();
        $this->loadRoutes($routes);

        $routeCollectionAccessor = new RouteCollectionAccessor($routes);
        /** @var Route $route */
        foreach ($routes as $route) {
            $this->routeOptionsResolver->resolve($route, $routeCollectionAccessor);
        }

        return $routes;
    }

    protected function loadRoutes(RouteCollection $routes)
    {
        $resources = $this->getResources();
        foreach ($resources as $resource) {
            $resourceRoutesCollection = $this->import($resource);

            $this->updateRoutesPriority($resourceRoutesCollection);
            $routes->addCollection($resourceRoutesCollection);
        }
    }

    protected function updateRoutesPriority(RouteCollection $resourceRoutesCollection): void
    {
        foreach ($resourceRoutesCollection->all() as $name => $route) {
            if ($route->getOption('priority')) {
                // Remove route with the same name first because just replacing
                // would not place the new route at the end of the array.
                $resourceRoutesCollection->remove($name);

                $resourceRoutesCollection->add($name, $route, $route->getOption('priority'));
            }
        }
    }

    /**
     * Returns the list of resources to be loaded by this loader
     *
     * @return array
     */
    abstract protected function getResources();
}
