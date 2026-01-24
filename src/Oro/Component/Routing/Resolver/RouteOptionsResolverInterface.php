<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;

/**
 * Defines the contract for resolving route options and applying route modifications.
 *
 * Implementations process route options and apply transformations to routes
 * and route collections based on those options.
 */
interface RouteOptionsResolverInterface
{
    /**
     * Performs the route modifications based on its options
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes);
}
