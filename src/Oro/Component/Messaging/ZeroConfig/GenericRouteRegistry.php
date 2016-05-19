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
        $this->routes[$route->getTopicName()][] = $route;
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
    public function getRoutes($topicName)
    {
        if (isset($this->routes[$topicName])) {
            return $this->routes[$topicName];
        }

        return [];
    }
}
