<?php

namespace Oro\Component\Routing\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Component\Routing\RouteCollectionUtil;

trait ApiDocExtractorTrait
{
    /** @var RouteOptionsResolverInterface|null */
    protected $routeOptionsResolver;

    /**
     * {@inheritdoc}
     */
    public function setRouteOptionsResolver(RouteOptionsResolverInterface $routeOptionsResolver)
    {
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    /**
     * @param Route[] $routes
     *
     * @return Route[]
     */
    protected function processRoutes(array $routes)
    {
        if (null !== $this->routeOptionsResolver) {
            $routeCollection = new EnhancedRouteCollection($routes);
            $routeCollectionAccessor = new RouteCollectionAccessor($routeCollection);
            /** @var Route $route */
            foreach ($routeCollection as $route) {
                $this->routeOptionsResolver->resolve($route, $routeCollectionAccessor);
            }
            $routes = $routeCollection->all();
        }

        return RouteCollectionUtil::filterHidden($routes);
    }
}
