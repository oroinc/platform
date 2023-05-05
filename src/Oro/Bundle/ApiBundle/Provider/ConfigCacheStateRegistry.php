<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;

/**
 * The registry that allows to get a service to check a configuration cache state
 * for a specific request type.
 */
class ConfigCacheStateRegistry
{
    /** @var array [[config cache state service, request type expression], ...] */
    private array $states;
    private RequestExpressionMatcher $matcher;
    /** @var ConfigCacheStateInterface[] [request type => ConfigCacheStateInterface, ...] */
    private array $cache = [];

    /**
     * @param array                    $states [[config cache state service, request type expression], ...]
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $states, RequestExpressionMatcher $matcher)
    {
        $this->states = $states;
        $this->matcher = $matcher;
    }

    /**
     * Returns the config cache state service for the given request type.
     *
     * @throws \LogicException if a config cache state service does not exist for the given request type
     */
    public function getConfigCacheState(RequestType $requestType): ConfigCacheStateInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $state = null;
        foreach ($this->states as [$currentState, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $state = $currentState;
                break;
            }
        }
        if (null === $state) {
            throw new \LogicException(sprintf(
                'Cannot find a config cache state service for the request "%s".',
                (string)$requestType
            ));
        }

        $this->cache[$cacheKey] = $state;

        return $state;
    }
}
