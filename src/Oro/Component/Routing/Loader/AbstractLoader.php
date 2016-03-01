<?php

namespace Oro\Component\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Component\Routing\Resolver\SortableRouteCollection;

abstract class AbstractLoader extends Loader
{
    /** @var RouteOptionsResolverInterface */
    protected $routeOptionsResolver;

    /**
     * @param RouteOptionsResolverInterface $routeOptionsResolver
     */
    public function __construct(RouteOptionsResolverInterface $routeOptionsResolver)
    {
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $routes = new SortableRouteCollection();

        $resources = $this->getResources();
        foreach ($resources as $resource) {
            $routes->addCollection($this->resolve($resource)->load($resource));
        }

        $routeCollectionAccessor = new RouteCollectionAccessor($routes);
        /** @var Route $route */
        foreach ($routes as $route) {
            $this->routeOptionsResolver->resolve($route, $routeCollectionAccessor);
        }

        $routes->sortByPriority();

        return $routes;
    }

    /**
     * Returns the list of resources to be loaded by this loader
     *
     * @return array
     */
    abstract protected function getResources();
}
