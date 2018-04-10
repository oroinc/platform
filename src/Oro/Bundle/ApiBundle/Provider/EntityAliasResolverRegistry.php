<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /** @var EntityAliasResolver[] [request type => EntityAliasResolver, ...] */
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
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $entityAliasResolverServiceId = null;
        foreach ($this->entityAliasResolvers as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $entityAliasResolverServiceId = $serviceId;
                break;
            }
        }
        if (null === $entityAliasResolverServiceId) {
            throw new \LogicException(
                sprintf('Cannot find an entity alias resolver for the request "%s".', (string)$requestType)
            );
        }

        /** @var EntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = $this->container->get($entityAliasResolverServiceId);
        $this->cache[$cacheKey] = $entityAliasResolver;

        return $entityAliasResolver;
    }

    /**
     * Warms up the cache of all entity alias resolvers.
     */
    public function warmUpCache(): void
    {
        $this->cache = [];
        foreach ($this->entityAliasResolvers as list($serviceId, $expression)) {
            /** @var EntityAliasResolver $entityAliasResolver */
            $entityAliasResolver = $this->container->get($serviceId);
            $entityAliasResolver->warmUpCache();
        }
    }

    /**
     * Clears the cache of all entity alias resolvers.
     */
    public function clearCache(): void
    {
        $this->cache = [];
        foreach ($this->entityAliasResolvers as list($serviceId, $expression)) {
            /** @var EntityAliasResolver $entityAliasResolver */
            $entityAliasResolver = $this->container->get($serviceId);
            $entityAliasResolver->clearCache();
        }
    }
}
