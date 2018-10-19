<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains all providers of mandatory fields and delegates retrieving of such fields
 * to child providers suitable for a specific request type.
 */
class MandatoryFieldProviderRegistry
{
    /** @var array [[provider service id, request type expression], ...] */
    private $providers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param array                    $providers [[provider service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $providers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->providers = $providers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets the list of mandatory fields for the given entity and suitable for the given request type.
     *
     * @param string      $entityClass The class name of an entity
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[]
     */
    public function getMandatoryFields(string $entityClass, RequestType $requestType): array
    {
        $result = [];
        foreach ($this->providers as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                /** @var MandatoryFieldProviderInterface $provider */
                $provider = $this->container->get($serviceId);
                $fields = $provider->getMandatoryFields($entityClass);
                if (!empty($fields)) {
                    $result[] = $fields;
                }
            }
        }
        if (!empty($result)) {
            $result = \array_merge(...$result);
            $result = \array_values(\array_unique($result));
        }

        return $result;
    }
}
