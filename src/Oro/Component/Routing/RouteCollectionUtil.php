<?php

namespace Oro\Component\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionUtil
{
    /**
     * Copies all routers not marked as hidden from a source to a destination collection.
     * If the destination collection is not specifies the new instance of RouteCollection will be used.
     *
     * @param RouteCollection      $src  The source route collection
     * @param RouteCollection|null $dest The destination route collection
     *
     * @return RouteCollection The destination route collection
     */
    public static function cloneWithoutHidden(RouteCollection $src, RouteCollection $dest = null)
    {
        if (null === $dest) {
            $dest = new RouteCollection();
        }

        $routes = $src->all();
        foreach ($routes as $name => $route) {
            if (!$route->getOption('hidden')) {
                $dest->add($name, $route);
            }
        }

        $resources = $src->getResources();
        foreach ($resources as $resource) {
            $dest->addResource($resource);
        }

        return $dest;
    }

    /**
     * Returns a copy of a given routes but without routers not marked as hidden.
     *
     * @param Route[] $routes
     *
     * @return Route[]
     */
    public static function filterHidden(array $routes)
    {
        $result = [];
        foreach ($routes as $name => $route) {
            if (!$route->getOption('hidden')) {
                $result[$name] = $route;
            }
        }

        return $result;
    }
}
