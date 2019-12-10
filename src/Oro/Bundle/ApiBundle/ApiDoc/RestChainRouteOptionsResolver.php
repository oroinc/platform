<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Delegates the route modifications based on its options to all applicable child resolvers.
 */
class RestChainRouteOptionsResolver implements RouteOptionsResolverInterface, ResetInterface
{
    /** @var array [[RouteOptionsResolverInterface, view name], ...] */
    private $resolvers = [];

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var array [view name => underlying view name, ...] */
    private $underlyingViews;

    /**
     * @param array               $resolvers       [[RouteOptionsResolverInterface, view name], ...]
     *                                             The view name can be NULL if the route option resolver
     *                                             is applicable to all views
     * @param RestDocViewDetector $docViewDetector
     * @param array               $underlyingViews [view name => underlying view name, ...]
     */
    public function __construct(
        array $resolvers,
        RestDocViewDetector $docViewDetector,
        array $underlyingViews
    ) {
        $this->resolvers = $resolvers;
        $this->docViewDetector = $docViewDetector;
        $this->underlyingViews = $underlyingViews;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (empty($this->resolvers)) {
            return;
        }

        $view = $this->docViewDetector->getView();
        if (!$view) {
            return;
        }

        $underlyingView = $this->underlyingViews[$view] ?? null;
        /** @var RouteOptionsResolverInterface $resolver */
        foreach ($this->resolvers as list($resolver, $resolverView)) {
            if (null === $resolverView || $resolverView === $view || $resolverView === $underlyingView) {
                $resolver->resolve($route, $routes);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        foreach ($this->resolvers as list($resolver, $resolverView)) {
            if ($resolver instanceof ResetInterface) {
                $resolver->reset();
            }
        }
    }
}
