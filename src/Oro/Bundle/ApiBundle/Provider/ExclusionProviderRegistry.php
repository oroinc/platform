<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

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

    /** @var ExclusionProviderInterface[] [request type => exclusion provider, ...] */
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
        if (isset($this->cache[(string)$requestType])) {
            return $this->cache[(string)$requestType];
        }

        $exclusionProviderServiceId = null;
        foreach ($this->exclusionProviders as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $exclusionProviderServiceId = $serviceId;
                break;
            }
        }
        if (null === $exclusionProviderServiceId) {
            throw new \LogicException(
                sprintf('Cannot find a exclusion provider for the request "%s".', (string)$requestType)
            );
        }

        /** @var ExclusionProviderInterface $exclusionProvider */
        $exclusionProvider = $this->container->get($exclusionProviderServiceId);
        $this->cache[(string)$requestType] = $exclusionProvider;

        return $exclusionProvider;
    }
}
