<?php

namespace Oro\Component\Routing;

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
    public function __construct($route, array $routeParameters = null)
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
