<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The registry that allows to get the Data API resources configuration bag
 * for a specific request type.
 */
class ConfigBagRegistry
{
    /** @var array [[config bag service id, request type expression], ...] */
    private $configBags;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var ConfigBagInterface[] [request type => ConfigBagInterface, ...] */
    private $cache = [];

    /**
     * @param array                    $configBags [[config bag service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $configBags,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->configBags = $configBags;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the config bag that contains API resources configuration for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return ConfigBagInterface
     *
     * @throws \LogicException if a config bag does not exist for the given request type
     */
    public function getConfigBag(RequestType $requestType): ConfigBagInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $configBagServiceId = null;
        foreach ($this->configBags as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $configBagServiceId = $serviceId;
                break;
            }
        }
        if (null === $configBagServiceId) {
            throw new \LogicException(
                sprintf('Cannot find a config bag for the request "%s".', (string)$requestType)
            );
        }

        /** @var ConfigBagInterface $configBag */
        $configBag = $this->container->get($configBagServiceId);
        $this->cache[$cacheKey] = $configBag;

        return $configBag;
    }
}
