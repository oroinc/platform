<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;

class ChainRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var RouteOptionsResolverInterface[] */
    protected $resolvers = [];

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (empty($this->resolvers)) {
            return;
        }

        foreach ($this->resolvers as $resolver) {
            $resolver->resolve($route, $routes);
        }
    }

    /**
     * Adds a route option resolver to the chain
     *
     * @param RouteOptionsResolverInterface $resolver The route option resolver
     */
    public function addResolver(RouteOptionsResolverInterface $resolver)
    {
        $this->resolvers[] = $resolver;
    }
}
