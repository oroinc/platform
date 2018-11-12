<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains all query modifiers and delegates a query modification
 * to child query modifiers suitable for a specific request type.
 */
class QueryModifierRegistry
{
    /** @var array [[query modifier service id, request type expression], ...] */
    private $queryModifiers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param array                    $queryModifiers [[query modifier service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
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
     */
    public function modifyQuery(QueryBuilder $qb, bool $skipRootEntity, RequestType $requestType): void
    {
        foreach ($this->queryModifiers as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                /** @var QueryModifierInterface $queryModifier */
                $queryModifier = $this->container->get($serviceId);
                $queryModifier->modify($qb, $skipRootEntity);
            }
        }
    }
}
