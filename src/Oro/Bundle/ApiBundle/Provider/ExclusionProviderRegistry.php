<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The registry that allows to get the exclusion provider for a specific request type.
 */
class ExclusionProviderRegistry
{
    /** @var array [[provider service id, request type expression], ...] */
    private $exclusionProviders;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var ExclusionProviderInterface[] [request type => ExclusionProviderInterface, ...] */
    private $cache = [];

    /**
     * @param array                    $exclusionProviders [[provider service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $exclusionProviders,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->exclusionProviders = $exclusionProviders;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the exclusion provider that contains exclusion rules for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return ExclusionProviderInterface
     *
     * @throws \LogicException if a exclusion provider does not exist for the given request type
     */
    public function getExclusionProvider(RequestType $requestType): ExclusionProviderInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $exclusionProviderServiceId = null;
        foreach ($this->exclusionProviders as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $exclusionProviderServiceId = $serviceId;
                break;
            }
        }
        if (null === $exclusionProviderServiceId) {
            throw new \LogicException(
                sprintf('Cannot find an exclusion provider for the request "%s".', (string)$requestType)
            );
        }

        /** @var ExclusionProviderInterface $exclusionProvider */
        $exclusionProvider = $this->container->get($exclusionProviderServiceId);
        $this->cache[$cacheKey] = $exclusionProvider;

        return $exclusionProvider;
    }
}
