<?php

namespace Oro\Component\Routing;

/**
 * Encapsulates route information including the route name and its parameters.
 *
 * This data object holds the route identifier and associated parameters,
 * providing a simple container for passing route information throughout
 * the application.
 */
class RouteData
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var array
     */
    protected $routeParameters;

    /**
     * @param string $route
     * @param array|null $routeParameters
     */
    public function __construct($route, ?array $routeParameters = null)
    {
        $this->route = $route;
        if ($routeParameters === null) {
            $routeParameters = [];
        }
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }
}
