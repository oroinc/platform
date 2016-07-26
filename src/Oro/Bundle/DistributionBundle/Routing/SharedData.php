<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * This object that can be used to share data between different loaders.
 */
class SharedData
{
    /** @var array */
    protected $routes = [];

    /**
     * @param string $resource
     *
     * @return RouteCollection|null
     */
    public function getRoutes($resource)
    {
        return isset($this->routes[$resource])
            ? $this->routes[$resource]
            : null;
    }

    /**
     * @param string          $resource
     * @param RouteCollection $routes
     */
    public function setRoutes($resource, RouteCollection $routes)
    {
        $this->routes[$resource] = $routes;
    }
}
