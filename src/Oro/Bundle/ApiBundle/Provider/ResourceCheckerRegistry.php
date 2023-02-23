<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the resource checker related services for a specific request type.
 */
class ResourceCheckerRegistry
{
    /** @var array [[resource type, config provider service id, checker service id, request type expression], ...] */
    private array $config;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    /** @var string[] [request type => resource type, ...] */
    private array $resourceTypes = [];
    /** @var ResourceCheckerConfigProvider[] [request type => ResourceCheckerConfigProvider, ...] */
    private array $resourceCheckerConfigProviders = [];
    /** @var ResourceCheckerInterface[] [request type => ResourceCheckerInterface, ...] */
    private array $resourceCheckers = [];

    public function __construct(
        array $config,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the resource type for the given request type.
     */
    public function getResourceType(RequestType $requestType): string
    {
        $cacheKey = (string)$requestType;
        if (isset($this->resourceTypes[$cacheKey])) {
            return $this->resourceTypes[$cacheKey];
        }

        $foundResourceType = null;
        foreach ($this->config as [$resourceType, $configServiceId, $checkerServiceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $foundResourceType = $resourceType;
                break;
            }
        }
        if (null === $foundResourceType) {
            throw new \LogicException(sprintf(
                'Cannot find a resource type for the request "%s".',
                (string)$requestType
            ));
        }

        $this->resourceTypes[$cacheKey] = $foundResourceType;

        return $foundResourceType;
    }

    /**
     * Returns the resource checker configuration provider for the given request type.
     */
    public function getResourceCheckerConfigProvider(RequestType $requestType): ResourceCheckerConfigProvider
    {
        $cacheKey = (string)$requestType;
        if (isset($this->resourceCheckerConfigProviders[$cacheKey])) {
            return $this->resourceCheckerConfigProviders[$cacheKey];
        }

        $foundResourceCheckerConfigProviderServiceId = null;
        foreach ($this->config as [$resourceType, $configServiceId, $checkerServiceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $foundResourceCheckerConfigProviderServiceId = $configServiceId;
                break;
            }
        }
        if (null === $foundResourceCheckerConfigProviderServiceId) {
            throw new \LogicException(sprintf(
                'Cannot find a resource checker config provider for the request "%s".',
                (string)$requestType
            ));
        }

        /** @var ResourceCheckerConfigProvider $resourceCheckerConfigProvider */
        $resourceCheckerConfigProvider = $this->container->get($foundResourceCheckerConfigProviderServiceId);
        $this->resourceCheckerConfigProviders[$cacheKey] = $resourceCheckerConfigProvider;

        return $resourceCheckerConfigProvider;
    }

    /**
     * Returns the resource checker for the given request type.
     */
    public function getResourceChecker(RequestType $requestType): ResourceCheckerInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->resourceCheckers[$cacheKey])) {
            return $this->resourceCheckers[$cacheKey];
        }

        $foundResourceCheckerServiceId = null;
        foreach ($this->config as [$resourceType, $configServiceId, $checkerServiceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $foundResourceCheckerServiceId = $checkerServiceId;
                break;
            }
        }
        if (null === $foundResourceCheckerServiceId) {
            throw new \LogicException(sprintf(
                'Cannot find a resource checker for the request "%s".',
                (string)$requestType
            ));
        }

        /** @var ResourceCheckerInterface $resourceChecker */
        $resourceChecker = $this->container->get($foundResourceCheckerServiceId);
        $this->resourceCheckers[$cacheKey] = $resourceChecker;

        return $resourceChecker;
    }
}
