<?php

namespace Oro\Component\Routing\Resolver;

/**
 * This interface can be implemented by classes that depends on a RouteOptionsResolver.
 */
interface RouteOptionsResolverAwareInterface
{
    /**
     * Sets the RouteOptionsResolver.
     *
     * @param RouteOptionsResolverInterface $routeOptionsResolver
     */
    public function setRouteOptionsResolver(RouteOptionsResolverInterface $routeOptionsResolver);
}
