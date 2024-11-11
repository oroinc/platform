<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Psr\Container\ContainerInterface;

/**
 * Contains all query modifiers and delegates a query modification
 * to child query modifiers suitable for a specific request type.
 */
class QueryModifierRegistry
{
    /** @var array [[query modifier service id, request type expression, options], ...] */
    private array $queryModifiers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    public function __construct(
        array $queryModifiers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->queryModifiers = $queryModifiers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Makes modifications of the given query builder and suitable for the given request type.
     *
     * @param QueryBuilder $qb             The query builder to modify
     * @param bool         $skipRootEntity Whether the root entity should be protected or not
     * @param RequestType  $requestType    The request type, for example "rest", "soap", etc.
     * @param array        $options        Additional options. [option name => option value, ...]
     */
    public function modifyQuery(
        QueryBuilder $qb,
        bool $skipRootEntity,
        RequestType $requestType,
        array $options = []
    ): void {
        foreach ($this->queryModifiers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                /** @var QueryModifierInterface $queryModifier */
                $queryModifier = $this->container->get($serviceId);
                if ($options && $queryModifier instanceof QueryModifierOptionsAwareInterface) {
                    $queryModifier->setOptions($options);
                    try {
                        $queryModifier->modify($qb, $skipRootEntity);
                    } finally {
                        $queryModifier->setOptions(null);
                    }
                } else {
                    $queryModifier->modify($qb, $skipRootEntity);
                }
            }
        }
    }
}
