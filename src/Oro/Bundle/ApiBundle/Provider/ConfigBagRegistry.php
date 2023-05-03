<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry that allows to get the API resources configuration bag
 * for a specific request type.
 */
class ConfigBagRegistry implements ResetInterface
{
    /** @var array [[config bag service id, request type expression], ...] */
    private array $configBags;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    /** @var ConfigBagInterface[] [request type => ConfigBagInterface, ...] */
    private array $cache = [];

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
     * @throws \LogicException if a config bag does not exist for the given request type
     */
    public function getConfigBag(RequestType $requestType): ConfigBagInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        /** @var ConfigBagInterface|null $configBag */
        $configBag = null;
        foreach ($this->configBags as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $configBag = $this->container->get($serviceId);
                break;
            }
        }
        if (null === $configBag) {
            throw new \LogicException(sprintf(
                'Cannot find a config bag for the request "%s".',
                (string)$requestType
            ));
        }

        $this->cache[$cacheKey] = $configBag;

        return $configBag;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        foreach ($this->cache as $configBag) {
            if ($configBag instanceof ResetInterface) {
                $configBag->reset();
            }
        }
        $this->cache = [];
    }
}
