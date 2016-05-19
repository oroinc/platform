<?php
namespace Oro\Component\Messaging\ZeroConfig;

class GenericRouteRegistry implements RouteRegistryInterface
{
    /**
     * @var array
     */
    protected $routes;

    public function __construct()
    {
        $this->routes = [];
    }

    /**
     * @param Route $route
     */
    public function addRoute(Route $route)
    {
        $this->routes[$route->getMessageName()][] = $route;
    }

    /**
     * @param Route[] $routes
     */
    public function setRoutes(array $routes)
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($messageName)
    {
        if (isset($this->routes[$messageName])) {
            return $this->routes[$messageName];
        }

        return [];
    }
}
