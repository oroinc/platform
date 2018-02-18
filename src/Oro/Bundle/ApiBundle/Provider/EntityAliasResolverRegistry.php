<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * The registry that allows to get the entity alias resolver for a specific request type.
 */
class EntityAliasResolverRegistry
{
    /** @var array [[resolver service id, request type expression], ...] */
    private $entityAliasResolvers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var EntityAliasResolver[] [request type => entity alias resolver, ...] */
    private $cache = [];

    /**
     * @param array                    $entityAliasResolvers [[resolver service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $entityAliasResolvers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->entityAliasResolvers = $entityAliasResolvers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the entity alias resolver that contains entity aliases for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return EntityAliasResolver
     *
     * @throws \LogicException if a entity alias resolver does not exist for the given request type
     */
    public function getEntityAliasResolver(RequestType $requestType): EntityAliasResolver
    {
        if (isset($this->cache[(string)$requestType])) {
            return $this->cache[(string)$requestType];
        }

        $entityAliasResolverServiceId = null;
        foreach ($this->entityAliasResolvers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $entityAliasResolverServiceId = $serviceId;
                break;
            }
        }
        if (null === $entityAliasResolverServiceId) {
            throw new \LogicException(
                sprintf('Cannot find a entity alias resolver for the request "%s".', (string)$requestType)
            );
        }

        /** @var EntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = $this->container->get($entityAliasResolverServiceId);
        $this->cache[(string)$requestType] = $entityAliasResolver;

        return $entityAliasResolver;
    }

    /**
     * Warms up the cache of all entity alias resolvers.
     */
    public function warmUpCache()
    {
        foreach ($this->entityAliasResolvers as [$serviceId, $expression]) {
            /** @var EntityAliasResolver $entityAliasResolver */
            $entityAliasResolver = $this->container->get($serviceId);
            $entityAliasResolver->warmUpCache();
        }
    }

    /**
     * Clears the cache of all entity alias resolvers.
     */
    public function clearCache()
    {
        foreach ($this->entityAliasResolvers as [$serviceId, $expression]) {
            /** @var EntityAliasResolver $entityAliasResolver */
            $entityAliasResolver = $this->container->get($serviceId);
            $entityAliasResolver->clearCache();
        }
    }
}
