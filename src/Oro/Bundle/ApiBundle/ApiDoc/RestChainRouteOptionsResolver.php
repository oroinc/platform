<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

class RestChainRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /**
     * @param RestDocViewDetector $docViewDetector
     */
    public function __construct(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    /** @var array [[RouteOptionsResolverInterface, view], ...] */
    protected $resolvers = [];

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (empty($this->resolvers)) {
            return;
        }

        $view = $this->docViewDetector->getView();
        if (empty($view)) {
            return;
        }

        foreach ($this->resolvers as $item) {
            /** @var RouteOptionsResolverInterface $resolver */
            list($resolver, $resolverView) = $item;
            if (null === $resolverView || $resolverView === $view) {
                $resolver->resolve($route, $routes);
            }
        }
    }

    /**
     * Adds a route option resolver to the chain.
     * The resolvers are executed in the order they are added.
     *
     * @param RouteOptionsResolverInterface $resolver The route option resolver
     * @param string|null                   $view     The name of a view the route option resolver is applicable to
     *                                                If NULL the route option resolver will work for all views
     */
    public function addResolver(RouteOptionsResolverInterface $resolver, $view = null)
    {
        $this->resolvers[] = [$resolver, $view];
    }
}
