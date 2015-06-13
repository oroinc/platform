<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionAccessor
{
    /** @var RouteCollection */
    protected $collection;

    /** @var array */
    protected $routeMap;

    /**
     * @param RouteCollection $collection
     */
    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Tries to find a route by its path and methods.
     *
     * @param string   $routePath
     * @param string[] $routeMethods
     *
     * @return Route|null
     */
    public function findRouteByPath($routePath, $routeMethods)
    {
        if (null === $this->routeMap) {
            $this->routeMap = [];
            /** @var Route $route */
            foreach ($this->collection->all() as $name => $route) {
                $this->routeMap[$this->getRouteKey($route->getPath(), $route->getMethods())] = $name;
            }
        }

        $key = $this->getRouteKey($routePath, $routeMethods);

        if (!isset($this->routeMap[$key])) {
            return null;
        }

        return $this->collection->get($this->routeMap[$key]);
    }

    /**
     * @param string   $routePath
     * @param string[] $routeMethods
     *
     * @return string
     */
    protected function getRouteKey($routePath, $routeMethods)
    {
        return implode('|', $routeMethods) . $routePath;
    }
}
