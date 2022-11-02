<?php

namespace Oro\Component\Routing\Resolver;

use Symfony\Component\Routing\Route;

/**
 * Delegates the resolving of route options to child resolvers.
 */
class ChainRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var iterable|RouteOptionsResolverInterface[] */
    private $resolvers;

    /**
     * @param iterable|RouteOptionsResolverInterface[] $resolvers
     */
    public function __construct(iterable $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        foreach ($this->resolvers as $resolver) {
            $resolver->resolve($route, $routes);
        }
    }
}
