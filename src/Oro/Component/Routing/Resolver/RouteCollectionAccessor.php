<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;

class RouteCollectionAccessor
{
    /** @var EnhancedRouteCollection */
    protected $collection;

    /** @var array */
    protected $routeMap;

    /**
     * @param EnhancedRouteCollection $collection
     */
    public function __construct(EnhancedRouteCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Gets a route by path and methods.
     *
     * @param string   $routePath
     * @param string[] $routeMethods
     *
     * @return Route|null A Route instance or null when not found
     */
    public function getByPath($routePath, $routeMethods)
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
     * Gets the name of the given route.
     *
     * @param Route $route
     *
     * @return string|null The route name or null when not found
     */
    public function getName(Route $route)
    {
        $found = array_search($route, $this->collection->all(), true);

        return false !== $found ? $found : null;
    }

    /**
     * Gets a route by name.
     *
     * @param string $routeName The route name
     *
     * @return Route|null A Route instance or null when not found
     */
    public function get($routeName)
    {
        return $this->collection->get($routeName);
    }

    /**
     * Adds a route to the end of the route collection.
     *
     * @param string $routeName The route name
     * @param Route  $route     A Route instance
     */
    public function append($routeName, Route $route)
    {
        $this->collection->insert($routeName, $route, null);
        if (null !== $this->routeMap) {
            $key                  = $this->getRouteKey($route->getPath(), $route->getMethods());
            $this->routeMap[$key] = $routeName;
        }
    }

    /**
     * Adds a route to the beginning of the route collection.
     *
     * @param string $routeName The route name
     * @param Route  $route     A Route instance
     */
    public function prepend($routeName, Route $route)
    {
        $this->collection->insert($routeName, $route, null, true);
        if (null !== $this->routeMap) {
            $key                  = $this->getRouteKey($route->getPath(), $route->getMethods());
            $this->routeMap[$key] = $routeName;
        }
    }

    /**
     * Adds a new route near to the specified target route.
     *
     * @param string $routeName       The route name
     * @param Route  $route           A Route instance
     * @param string $targetRouteName The name of a route near to which the new route should be added
     * @param bool   $prepend         Determines whether the new route should be added before or after the target route
     */
    public function insert($routeName, Route $route, $targetRouteName, $prepend = false)
    {
        $this->collection->insert($routeName, $route, $targetRouteName, $prepend);
        if (null !== $this->routeMap) {
            $key                  = $this->getRouteKey($route->getPath(), $route->getMethods());
            $this->routeMap[$key] = $routeName;
        }
    }

    /**
     * Removes a route from the route collection.
     *
     * @param string $routeName The route name
     */
    public function remove($routeName)
    {
        $route = $this->collection->get($routeName);
        if ($route) {
            $this->collection->remove($routeName);
            if (null !== $this->routeMap) {
                $key = $this->getRouteKey($route->getPath(), $route->getMethods());
                unset($this->routeMap[$key]);
            }
        }
    }

    /**
     * Makes a clone of the given Route object.
     *
     * @param Route $route
     *
     * @return Route
     */
    public function cloneRoute(Route $route)
    {
        return new Route(
            $route->getPath(),
            $route->getDefaults(),
            $route->getRequirements(),
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );
    }

    /**
     * Returns a string which can be used as a name of auto generated route.
     *
     * @param string $routeNameSample
     *
     * @return string
     */
    public function generateRouteName($routeNameSample)
    {
        return sprintf(
            '%s_auto_%d',
            $routeNameSample,
            $this->collection->count() + 1
        );
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
