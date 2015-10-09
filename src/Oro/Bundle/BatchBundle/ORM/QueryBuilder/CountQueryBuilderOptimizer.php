<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CountQueryBuilderOptimizer
{
    /** @var QueryBuilderTools */
    protected $qbTools;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var QueryOptimizationContext */
    protected $context;

    /**
     * @param QueryBuilderTools|null $qbTools
     */
    public function __construct(QueryBuilderTools $qbTools = null)
    {
        if (!$qbTools) {
            $qbTools = new QueryBuilderTools();
        }
        $this->qbTools = $qbTools;
    }

    /**
     * Sets an event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get optimized query builder for count calculation.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->context = new QueryOptimizationContext($queryBuilder, $this->qbTools);
        try {
            $qb = $this->buildCountQueryBuilder();
            // remove a link to the context
            $this->context = null;

            return $qb;
        } catch (\Exception $e) {
            // make sure that a link to the context is removed even if an exception occurred
            $this->context = null;
            // rethrow an exception
            throw $e;
        }
    }

    /**
     * @return QueryBuilder
     */
    protected function buildCountQueryBuilder()
    {
        $optimizedQueryBuilder = clone $this->context->getOriginalQueryBuilder();
        $optimizedQueryBuilder
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('where')
            ->resetDQLPart('having');

        $originalQueryParts = $this->context->getOriginalQueryBuilder()->getDQLParts();

        $fieldsToSelect = [];
        if ($originalQueryParts['groupBy']) {
            $groupBy            = (array)$originalQueryParts['groupBy'];
            $groupByFields      = $this->getSelectFieldFromGroupBy($groupBy);
            $usedGroupByAliases = [];
            foreach ($groupByFields as $key => $groupByField) {
                $alias                = '_groupByPart' . $key;
                $usedGroupByAliases[] = $alias;
                $fieldsToSelect[]     = $groupByField . ' as ' . $alias;
            }
            $optimizedQueryBuilder->groupBy(implode(', ', $usedGroupByAliases));
        } elseif (!$originalQueryParts['where'] && $originalQueryParts['having']) {
            // If there is no where and group by, but having is present - convert having to where.
            $originalQueryParts['where']  = $originalQueryParts['having'];
            $originalQueryParts['having'] = null;
            $optimizedQueryBuilder->resetDQLPart('having');
        }

        if ($originalQueryParts['having']) {
            $optimizedQueryBuilder->having(
                $this->qbTools->replaceAliasesWithFields($originalQueryParts['having'])
            );
        }

        if ($originalQueryParts['join']) {
            $this->addJoins($optimizedQueryBuilder, $originalQueryParts);
        }
        if (!$originalQueryParts['groupBy']) {
            /** @var Expr\From $from */
            foreach ($originalQueryParts['from'] as $from) {
                $fieldNames = $this->context->getClassMetadata($from->getFrom())->getIdentifierFieldNames();
                foreach ($fieldNames as $fieldName) {
                    $fieldsToSelect[] = $from->getAlias() . '.' . $fieldName;
                }
            }
        }

        if ($originalQueryParts['where']) {
            $optimizedQueryBuilder->where(
                $this->qbTools->replaceAliasesWithFields($originalQueryParts['where'])
            );
        }

        $optimizedQueryBuilder->select(array_unique($fieldsToSelect));
        $this->qbTools->fixUnusedParameters($optimizedQueryBuilder);

        return $optimizedQueryBuilder;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $optimizedQueryBuilder
     * @param array        $originalQueryParts
     */
    protected function addJoins(QueryBuilder $optimizedQueryBuilder, array $originalQueryParts)
    {
        // Collect list of tables which should be added to new query
        $whereAliases   = $this->qbTools->getUsedTableAliases($originalQueryParts['where']);
        $groupByAliases = $this->qbTools->getUsedTableAliases($originalQueryParts['groupBy']);
        $havingAliases  = $this->qbTools->getUsedTableAliases($originalQueryParts['having']);
        $joinAliases    = array_merge($whereAliases, $groupByAliases, $havingAliases);
        $joinAliases    = array_unique($joinAliases);

        // this joins cannot be removed outside of this class
        $requiredJoinAliases = $joinAliases;

        $joinAliases = array_merge(
            $joinAliases,
            $this->getNonSymmetricJoinAliases(
                $originalQueryParts['from'],
                $originalQueryParts['join'],
                $groupByAliases
            )
        );

        $rootAliases = [];
        /** @var Expr\From $from */
        foreach ($originalQueryParts['from'] as $from) {
            $rootAliases[] = $from->getAlias();
            $joinAliases   = array_merge(
                $joinAliases,
                $this->qbTools->getUsedJoinAliases($originalQueryParts['join'], $joinAliases, $from->getAlias())
            );
        }

        $allAliases          = $this->context->getAliases();
        $joinAliases         = array_intersect(array_diff(array_unique($joinAliases), $rootAliases), $allAliases);
        $requiredJoinAliases = array_intersect(array_diff($requiredJoinAliases, $rootAliases), $allAliases);

        $joinAliases = $this->dispatchQueryOptimizationEvent($joinAliases, $requiredJoinAliases);

        foreach ($rootAliases as $rootAlias) {
            if (!isset($originalQueryParts['join'][$rootAlias])) {
                continue;
            }
            /** @var Expr\Join $join */
            foreach ($originalQueryParts['join'][$rootAlias] as $join) {
                $alias = $join->getAlias();
                // To count results number join all tables with inner join and required to tables
                if ($join->getJoinType() === Expr\Join::INNER_JOIN || in_array($alias, $joinAliases, true)) {
                    $condition = $this->qbTools->replaceAliasesWithFields($join->getCondition());
                    $condition = $this->qbTools->replaceAliasesWithJoinPaths($condition);

                    if ($join->getJoinType() === Expr\Join::INNER_JOIN) {
                        $optimizedQueryBuilder->innerJoin(
                            $join->getJoin(),
                            $alias,
                            $join->getConditionType(),
                            $condition,
                            $join->getIndexBy()
                        );
                    } else {
                        $optimizedQueryBuilder->leftJoin(
                            $join->getJoin(),
                            $alias,
                            $join->getConditionType(),
                            $condition,
                            $join->getIndexBy()
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string[] $joinAliases         A list of joins to be added to an optimized query
     * @param string[] $requiredJoinAliases A list of joins that cannot be removed
     *                                      even if it is requested by a listener
     *
     * @return string[]
     */
    protected function dispatchQueryOptimizationEvent($joinAliases, $requiredJoinAliases)
    {
        if (null !== $this->eventDispatcher) {
            $event = new CountQueryOptimizationEvent($this->context, $joinAliases);
            $this->eventDispatcher->dispatch(CountQueryOptimizationEvent::EVENT_NAME, $event);
            $toRemoveAliases = $event->getRemovedOptimizedQueryJoinAliases();
            if (!empty($toRemoveAliases)) {
                $toRemoveAliases = array_diff($toRemoveAliases, $requiredJoinAliases);
                $joinAliases     = array_diff($joinAliases, $toRemoveAliases);
            }
        }

        return $joinAliases;
    }

    /**
     * @param Expr\GroupBy[] $groupBy
     *
     * @return array
     */
    protected function getSelectFieldFromGroupBy(array $groupBy)
    {
        $expressions = [];
        foreach ($groupBy as $groupByPart) {
            foreach ($groupByPart->getParts() as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        }

        return $expressions;
    }

    /**
     * @param string $groupByPart
     *
     * @return array
     */
    protected function getSelectFieldFromGroupByPart($groupByPart)
    {
        $expressions = [];
        if (strpos($groupByPart, ',') !== false) {
            $groupByParts = explode(',', $groupByPart);
            foreach ($groupByParts as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        } else {
            $groupByPart   = trim($groupByPart);
            $groupByPart   = $this->qbTools->replaceAliasesWithFields($groupByPart);
            $expressions[] = $groupByPart;
        }

        return $expressions;
    }

    /**
     * Get join aliases that will produce non symmetric results
     * (many-to-one left join or one-to-many right join)
     *
     * @param Expr\From[] $fromStatements
     * @param array       $joins
     * @param string      $groupByAliases the aliases that was used in GROUP BY statement
     *
     * @return array
     */
    protected function getNonSymmetricJoinAliases($fromStatements, $joins, $groupByAliases)
    {
        $aliases = [];

        $dependencies = $this->getNonSymmetricJoinDependencies($fromStatements, $joins);
        foreach ($dependencies as $alias => $joinInfo) {
            $join = $this->context->getJoinByAlias($alias);

            // skip joins that is not left joins or was not used in GROUP BY statement,
            // if it was a GROUP BY statement at all
            if ($join->getJoinType() !== Expr\Join::LEFT_JOIN
                || (!empty($groupByAliases) && !in_array($alias, $groupByAliases, true))
            ) {
                continue;
            }

            // if there is just association expression like `alias.fieldName`
            $parts = explode('.', $join->getJoin());
            if (count($parts) === 2) {
                $associationType = $this->context->getAssociationType($parts[0], $parts[1]);
                if ($associationType & ClassMetadata::TO_MANY) {
                    $aliases[] = $alias;
                }
            } else { // otherwise there is an entity name
                $joinEntityClass = $this->context->getEntityClassByAlias($join->getAlias());

                $aliasToAssociation = []; // [alias => associationName, ...]
                $fields             = $this->qbTools->getFields($join->getCondition());
                foreach ($fields as $field) {
                    $fieldParts                         = explode('.', $field);
                    $aliasToAssociation[$fieldParts[0]] = $fieldParts[1];
                }

                // for each alias the current join is depended (this alias is called as $dependeeAlias):
                // - resolve it's entity class
                // - check the relations
                foreach ($joinInfo[1] as $dependeeAlias) {
                    $dependeeClass   = $this->context->getEntityClassByAlias($dependeeAlias);
                    $associationName = array_key_exists($dependeeAlias, $aliasToAssociation)
                        ? $aliasToAssociation[$dependeeAlias]
                        : $aliasToAssociation[$alias];

                    if ($this->isToManyAssociation($dependeeClass, $associationName, $joinEntityClass)) {
                        $aliases[] = $alias;
                    }
                }
            }
        }

        return array_unique($aliases);
    }

    /**
     * @param Expr\From[] $fromStatements
     * @param array       $joins
     *
     * @return array
     */
    protected function getNonSymmetricJoinDependencies($fromStatements, $joins)
    {
        $dependencies = [];
        foreach ($fromStatements as $from) {
            $rootAlias = $from->getAlias();
            if (isset($joins[$rootAlias])) {
                $dependencies = array_merge(
                    $dependencies,
                    $this->qbTools->getAllDependencies($rootAlias, $joins[$rootAlias])
                );
            }
        }

        return $dependencies;
    }

    /**
     * @param string $entityClass
     * @param string $associationName
     * @param string $targetEntityClass
     *
     * @return bool
     */
    protected function isToManyAssociation($entityClass, $associationName, $targetEntityClass)
    {
        // check owning side association
        $associationType = $this->getAssociationType($entityClass, $associationName, $targetEntityClass);
        if ($associationType & ClassMetadata::TO_MANY) {
            return true;
        }

        // check target side association
        $associationType = $this->getAssociationType($targetEntityClass, $associationName, $entityClass);
        if (in_array($associationType, [ClassMetadata::MANY_TO_MANY, ClassMetadata::MANY_TO_ONE], true)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the type of an association between the given entities
     *
     * @param string $entityClass
     * @param string $associationName
     * @param string $targetEntityClass
     *
     * @return int
     */
    protected function getAssociationType($entityClass, $associationName, $targetEntityClass)
    {
        $associations = $this->context->getClassMetadata($entityClass)
            ->getAssociationsByTargetClass($targetEntityClass);
        if (!array_key_exists($associationName, $associations)) {
            return 0;
        }

        return $associations[$associationName]['type'];
    }
}
