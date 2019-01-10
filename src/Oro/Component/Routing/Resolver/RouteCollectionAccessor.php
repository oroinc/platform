<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a set of methods to simplify the managing of a collection of routes.
 */
class RouteCollectionAccessor
{
    /** @var EnhancedRouteCollection */
    private $collection;

    /** @var array [route path => [route name => [string representation of methods, [method, ...], ...]], ...] */
    private $routeMap;

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
     * @param string   $routePath    The route name
     * @param string[] $routeMethods The uppercased HTTP methods
     * @param bool     $strict       TRUE if a searching route should have the exactly same methods
     *                               as the given methods
     *                               FALSE if a searching route should allow all the given methods,
     *                               it means that the given methods should be a subset of the searching route methods
     *                               or the searching route should allow all methods (its methods are empty)
     *
     * @return Route|null A Route instance or null when not found
     */
    public function getByPath($routePath, $routeMethods, $strict = true)
    {
        if (null === $this->routeMap) {
            $this->routeMap = [];
            $this->addCollectionToRouteMap($this->collection);
        }

        $routeName = null;
        if (isset($this->routeMap[$routePath])) {
            if ($routeMethods || $strict) {
                foreach ($this->routeMap[$routePath] as $name => list($methodsAsString, $methods)) {
                    if (self::isMethodsAlowed($methods, $methodsAsString, $routeMethods, $strict)) {
                        $routeName = $name;
                        break;
                    }
                }
            } else {
                $routeName = key($this->routeMap[$routePath]);
            }
        }

        return $routeName
            ? $this->collection->get($routeName)
            : null;
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
        $this->insert($routeName, $route, null, false);
    }

    /**
     * Adds a route to the beginning of the route collection.
     *
     * @param string $routeName The route name
     * @param Route  $route     A Route instance
     */
    public function prepend($routeName, Route $route)
    {
        $this->insert($routeName, $route, null, true);
    }

    /**
     * Adds a new route near to the specified target route.
     * If the target route name is empty adds a new route to the beginning or end of the route collection.
     *
     * @param string      $routeName       The route name
     * @param Route       $route           A Route instance
     * @param string|null $targetRouteName The name of a route near to which the new route should be added
     * @param bool        $prepend         Determines whether the new route should be added
     *                                     before or after the target route
     */
    public function insert($routeName, Route $route, $targetRouteName = null, $prepend = false)
    {
        $this->collection->insert($routeName, $route, $targetRouteName, $prepend);
        if (null !== $this->routeMap) {
            $this->addToRouteMap($routeName, $route->getPath(), $route->getMethods());
        }
    }

    /**
     * Adds a route collection near the specified target route.
     * If the target route name is empty adds a given route collection to the beginning or end of the route collection.
     *
     * @param RouteCollection $collection      A RouteCollection instance
     * @param string          $targetRouteName The name of a route near which the new route should be added
     * @param bool            $prepend         Determines whether the new route should be added
     *                                         before or after the target route
     */
    public function insertCollection(RouteCollection $collection, $targetRouteName = null, $prepend = false)
    {
        $this->collection->insertCollection($collection, $targetRouteName, $prepend);
        if (null !== $this->routeMap) {
            $this->addCollectionToRouteMap($collection);
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
        if (null !== $route) {
            $this->collection->remove($routeName);
            if (null !== $this->routeMap) {
                $this->removeFromRouteMap($routeName, $route->getPath());
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
     * @param RouteCollection $collection
     */
    private function addCollectionToRouteMap(RouteCollection $collection)
    {
        foreach ($collection->all() as $name => $route) {
            $this->addToRouteMap($name, $route->getPath(), $route->getMethods());
        }
    }

    /**
     * @param string   $routeName
     * @param string   $routePath
     * @param string[] $routeMethods
     */
    private function addToRouteMap($routeName, $routePath, $routeMethods)
    {
        $this->routeMap[$routePath][$routeName] = [
            self::convertMethodsToString($routeMethods),
            $routeMethods
        ];
    }

    /**
     * @param string $routeName
     * @param string $routePath
     */
    private function removeFromRouteMap($routeName, $routePath)
    {
        unset($this->routeMap[$routePath][$routeName]);
        if (empty($this->routeMap[$routePath])) {
            unset($this->routeMap[$routePath]);
        }
    }

    /**
     * @param string[] $methods
     * @param string   $methodsAsString
     * @param string[] $requestedMethods
     * @param bool     $strict
     *
     * @return bool
     */
    private static function isMethodsAlowed($methods, $methodsAsString, $requestedMethods, $strict)
    {
        if ($strict) {
            return self::convertMethodsToString($requestedMethods) === $methodsAsString;
        }

        return !$methods || !array_diff($requestedMethods, $methods);
    }

    /**
     * @param string[] $methods
     *
     * @return string
     */
    private static function convertMethodsToString($methods)
    {
        sort($methods);

        return implode('|', $methods);
    }
}
