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
     * @param string $messageName
     *
     * @param Route $route
     */
    public function addRoute($messageName, Route $route)
    {
        $this->routes[$messageName][] = $route;
    }

    /**
     * @param string $messageName
     *
     * @param Route[] $routes
     */
    public function setRoutes($messageName, array $routes)
    {
        foreach ($routes as $route) {
            $this->addRoute($messageName, $route);
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
