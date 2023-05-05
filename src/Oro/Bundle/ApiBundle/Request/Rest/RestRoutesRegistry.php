<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all routes providers for REST based APIs
 * and allows to get a provider suitable for a specific request type.
 */
class RestRoutesRegistry
{
    /** @var array [data type => [[provider, request type expression], ...], ...] */
    private array $providers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $providers [[provider, request type expression], ...]
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
     * Returns routes provider for a given request type.
     */
    public function getRoutes(RequestType $requestType): RestRoutes
    {
        foreach ($this->providers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                return $this->container->get($serviceId);
            }
        }

        throw new \LogicException(sprintf(
            'Cannot find a routes provider for the request "%s".',
            (string)$requestType
        ));
    }
}
