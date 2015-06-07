<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\Routing\Route;

class ChainRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var RouteOptionsResolverInterface[] */
    protected $resolvers = [];

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route)
    {
        if (empty($this->resolvers)) {
            return;
        }

        foreach ($this->resolvers as $resolver) {
            $resolver->resolve($route);
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
