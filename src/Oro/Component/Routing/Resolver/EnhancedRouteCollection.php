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
        $routes = $this->all();
        if (empty($targetRouteName)) {
            if (!$prepend || empty($routes)) {
                $this->add($routeName, $route);

                return;
            }

            $index = 0;
        } else {
            $index = array_search($targetRouteName, array_keys($routes), true);
        }

        $result             = array_slice($routes, 0, $index + ($prepend ? 0 : 1), true);
        $result[$routeName] = $route;
        $result             = array_merge($result, array_slice($routes, $index + ($prepend ? 0 : 1), null, true));
        $this->setRoutes($result);
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
        $r = new \ReflectionClass('Symfony\Component\Routing\RouteCollection');
        $p = $r->getProperty('routes');
        $p->setAccessible(true);
        $p->setValue($this, $routes);
    }
}
