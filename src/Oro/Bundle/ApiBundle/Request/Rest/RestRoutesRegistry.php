<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

/**
 * Contains all routes providers for REST based APIs
 * and allows to get a provider suitable for a specific request type.
 */
class RestRoutesRegistry
{
    /** @var array [data type => [[provider, request type expression], ...], ...] */
    private $providers;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param array                    $providers [[provider, request type expression], ...]
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $providers, RequestExpressionMatcher $matcher)
    {
        $this->providers = $providers;
        $this->matcher = $matcher;
    }

    /**
     * Returns routes provider for a given request type.
     *
     * @param RequestType $requestType
     *
     * @return RestRoutes
     */
    public function getRoutes(RequestType $requestType): RestRoutes
    {
        foreach ($this->providers as list($provider, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                return $provider;
            }
        }

        throw new \LogicException(
            sprintf('Cannot find a routes provider for the request "%s".', (string)$requestType)
        );
    }
}
