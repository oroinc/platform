<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class EnhancedRouteCollection extends RouteCollection
{
    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes = [])
    {
        if (!empty($routes)) {
            $this->setRoutes($routes);
        }
    }

    /**
     * Adds a new route near the specified target route.
     * If the target route name is empty adds a new route to the beginning or end of the route collection.
     *
     * @param string $routeName       The route name
     * @param Route  $route           A Route instance
     * @param string $targetRouteName The name of a route near which the new route should be added
     * @param bool   $prepend         Determines whether the new route should be added before or after the target route
     */
    public function insert($routeName, Route $route, $targetRouteName, $prepend = false)
    {
        $this->remove($routeName);
        $index = $this->findRouteIndex($targetRouteName);
        if (false === $index) {
            if ($prepend) {
                $this->setRoutes(array_merge([$routeName => $route], $this->all()));
            } else {
                $this->add($routeName, $route);
            }
        } else {
            if (!$prepend) {
                $index++;
            }
            $routes = $this->all();
            $result = array_slice($routes, 0, $index, true);
            $result[$routeName] = $route;
            $result = array_merge($result, array_slice($routes, $index, null, true));
            $this->setRoutes($result);
        }
    }

    /**
     * Searches the routes for a given route name and returns its index if successful.
     *
     * @param string $routeName
     *
     * @return int|bool The index of the route if it is found; otherwise, false.
     */
    protected function findRouteIndex($routeName)
    {
        if (empty($routeName)) {
            return false;
        }

        return array_search($routeName, array_keys($this->all()), true);
    }

    /**
     * Completely replaces existing routes.
     *
     * @param Route[] $routes
     */
    protected function setRoutes(array $routes)
    {
        // unfortunately $routes property is private and there is no other way
        // to insert a route at the specified position except to use the reflection
        $r = new \ReflectionClass(RouteCollection::class);
        $p = $r->getProperty('routes');
        $p->setAccessible(true);
        $p->setValue($this, $routes);
    }
}
