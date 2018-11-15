<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

/**
 * Contains all filter names providers
 * and allows to get a provider suitable for a specific request type.
 */
class FilterNamesRegistry
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
     * Returns filter names provider for a given request type.
     *
     * @param RequestType $requestType
     *
     * @return FilterNames
     */
    public function getFilterNames(RequestType $requestType): FilterNames
    {
        foreach ($this->providers as list($provider, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                return $provider;
            }
        }

        throw new \LogicException(
            sprintf('Cannot find a filter names provider for the request "%s".', (string)$requestType)
        );
    }
}
