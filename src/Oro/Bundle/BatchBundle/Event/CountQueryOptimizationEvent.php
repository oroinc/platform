<?php

namespace Oro\Bundle\BatchBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryOptimizationContext;

class CountQueryOptimizationEvent extends Event
{
    const EVENT_NAME = 'oro.entity.count_query.optimize';

    /** @var QueryOptimizationContext */
    protected $context;

    /** @var string[] */
    protected $aliases;

    /** @var string[] */
    protected $toRemoveAliases = [];

    /**
     * @param QueryOptimizationContext $context A query optimization context
     * @param string[]                 $aliases A list of join aliases to be added to optimized query
     */
    public function __construct(QueryOptimizationContext $context, array $aliases)
    {
        $this->context = $context;
        $this->aliases = $aliases;
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
        return $this->aliases;
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
     * Requests to remove a join from optimized query
     *
     * @param string $alias The alias of a join to be removed
     */
    public function removeJoinFromOptimizedQuery($alias)
    {
        if (!in_array($alias, $this->toRemoveAliases, true)) {
            $this->toRemoveAliases[] = $alias;
        }
    }
}
