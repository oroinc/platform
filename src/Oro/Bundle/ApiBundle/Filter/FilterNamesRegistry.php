<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all filter names providers
 * and allows to get a provider suitable for a specific request type.
 */
class FilterNamesRegistry
{
    /** @var array [data type => [[provider service id, request type expression], ...], ...] */
    private array $providers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $providers [[provider service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $providers, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->providers = $providers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns filter names provider for a given request type.
     */
    public function getFilterNames(RequestType $requestType): FilterNames
    {
        foreach ($this->providers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                return $this->container->get($serviceId);
            }
        }

        throw new \LogicException(sprintf(
            'Cannot find a filter names provider for the request "%s".',
            (string)$requestType
        ));
    }
}
