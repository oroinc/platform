<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\PhpUtils\ArrayUtil;

class SortableRouteCollection extends RouteCollection
{
    /**
     * Sorts the routes by priority
     */
    public function sortByPriority()
    {
        // unfortunately $routes property is private and there is no any way
        // to sort routes except to use the reflection
        $r = new \ReflectionClass('Symfony\Component\Routing\RouteCollection');
        $p = $r->getProperty('routes');
        $p->setAccessible(true);

        $routes = $p->getValue($this);
        ArrayUtil::sortBy(
            $routes,
            false,
            function (Route $route) {
                return $route->getOption('priority') ?: 0;
            }
        );
        $p->setValue($this, $routes);
    }
}
