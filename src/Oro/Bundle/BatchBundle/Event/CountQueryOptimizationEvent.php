<?php

namespace Oro\Bundle\BatchBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryOptimizationContext;

class CountQueryOptimizationEvent extends Event
{
    /** @var QueryOptimizationContext */
    protected $context;

    /** @var string[] */
    protected $requiredAliases;

    /** @var string[] */
    protected $toRemoveAliases = [];

    /**
     * @param QueryOptimizationContext $context
     * @param string[]                 $requiredAliases
     */
    public function __construct(QueryOptimizationContext $context, array $requiredAliases)
    {
        $this->context         = $context;
        $this->requiredAliases = $requiredAliases;
    }

    /**
     * Gets original query builder
     *
     * @return QueryBuilder
     */
    public function getOriginalQueryBuilder()
    {
        return $this->context->getOriginalQueryBuilder();
    }

    /**
     * Gets a query optimization context
     *
     * @return QueryOptimizationContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets a list of join aliases to be added to optimized query
     *
     * @return string[]
     */
    public function getOptimizedQueryJoinAliases()
    {
        return $this->requiredAliases;
    }

    /**
     * Gets a list of join aliases are requested to be removed from optimized query
     *
     * @return string[]
     */
    public function getRemovedOptimizedQueryJoinAliases()
    {
        return $this->toRemoveAliases;
    }

    /**
     * Requests to remove a join alias from optimized query
     *
     * @param string $alias
     */
    public function removeOptimizedQueryJoinAlias($alias)
    {
        if (!in_array($alias, $this->toRemoveAliases, true)) {
            $this->toRemoveAliases[] = $alias;
        }
    }
}
