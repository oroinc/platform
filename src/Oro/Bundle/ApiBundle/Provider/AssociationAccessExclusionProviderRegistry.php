<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the association access exclusion provider for a specific request type.
 */
class AssociationAccessExclusionProviderRegistry
{
    /** @var array [[provider service id, request type expression], ...] */
    private array $providers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    /** @var AssociationAccessExclusionProviderInterface[] [request type => provider, ...] */
    private array $cache = [];

    /**
     * @param array                    $providers [[provider service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $providers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->providers = $providers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns a service that provides an information whether an access check to associations should be ignored
     * for the given request type.
     */
    public function getAssociationAccessExclusionProvider(
        RequestType $requestType
    ): AssociationAccessExclusionProviderInterface {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $providers = [];
        foreach ($this->providers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $providers[] = $this->container->get($serviceId);
            }
        }

        $provider = new ChainAssociationAccessExclusionProvider($providers);
        $this->cache[$cacheKey] = $provider;

        return $provider;
    }
}
