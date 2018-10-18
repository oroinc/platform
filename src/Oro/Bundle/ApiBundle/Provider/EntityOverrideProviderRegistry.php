<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The registry that allows to get the entity override provider for a specific request type.
 */
class EntityOverrideProviderRegistry
{
    /** @var array [[provider service id, request type expression], ...] */
    private $entityOverrideProviders;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var EntityOverrideProviderInterface[] [request type => EntityOverrideProviderInterface, ...] */
    private $cache = [];

    /**
     * @param array                    $entityOverrideProviders [[provider service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $entityOverrideProviders,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->entityOverrideProviders = $entityOverrideProviders;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the entity override provider that contains entity substitutions for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return EntityOverrideProviderInterface
     *
     * @throws \LogicException if a entity override provider does not exist for the given request type
     */
    public function getEntityOverrideProvider(RequestType $requestType): EntityOverrideProviderInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $entityOverrideProviderServiceId = null;
        foreach ($this->entityOverrideProviders as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $entityOverrideProviderServiceId = $serviceId;
                break;
            }
        }
        if (null === $entityOverrideProviderServiceId) {
            throw new \LogicException(
                sprintf('Cannot find an entity override provider for the request "%s".', (string)$requestType)
            );
        }

        /** @var EntityOverrideProviderInterface $entityOverrideProvider */
        $entityOverrideProvider = $this->container->get($entityOverrideProviderServiceId);
        $this->cache[$cacheKey] = $entityOverrideProvider;

        return $entityOverrideProvider;
    }
}
