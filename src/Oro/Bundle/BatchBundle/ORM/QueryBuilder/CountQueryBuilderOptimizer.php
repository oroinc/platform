<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Oro\Bundle\EntityBundle\Helper\RelationHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class optimizes query builder for count calculation
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CountQueryBuilderOptimizer
{
    /** @var QueryBuilderTools */
    protected $qbTools;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RelationHelper */
    protected $relationHelper;

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
     * @param RelationHelper $relationHelper
     */
    public function setRelationHelper(RelationHelper $relationHelper)
    {
        $this->relationHelper = $relationHelper;
    }

    /**
     * Get optimized query builder for count calculation.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getCountQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->context = new QueryOptimizationContext($queryBuilder, $this->qbTools);
        try {
            $qb = $this->buildCountQueryBuilder();
            // remove a link to the context
            $this->context = null;

            return $qb;
        } finally {
            // make sure that a link to the context is removed even if an exception occurred
            $this->context = null;
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
        $usedAliases = [];
        if ($originalQueryParts['groupBy']) {
            $groupBy            = (array)$originalQueryParts['groupBy'];
            $groupByFields      = $this->getSelectFieldFromGroupBy($groupBy);
            $usedGroupByAliases = [];
            foreach ($groupByFields as $key => $groupByField) {
                $alias                = '_groupByPart' . $key;
                $usedGroupByAliases[] = $alias;
                $fieldsToSelect[]     = $groupByField . ' as ' . $alias;
                $usedAliases[$groupByField] = $alias;
            }
            $optimizedQueryBuilder->groupBy(implode(', ', $usedGroupByAliases));
        } elseif (!$originalQueryParts['where'] && $originalQueryParts['having']) {
            // If there is no where and group by, but having is present - convert having to where.
            $originalQueryParts['where']  = $originalQueryParts['having'];
            $originalQueryParts['having'] = null;
            $optimizedQueryBuilder->resetDQLPart('having');
        }

        if ($originalQueryParts['having']) {
            $having = $this->qbTools->replaceAliasesWithFields($originalQueryParts['having']);

            $optimizedQueryBuilder->having(
                $this->prepareHavingClause($optimizedQueryBuilder, $usedAliases, $having)
            );
        }

        if (!$originalQueryParts['groupBy']) {
            $fieldsToSelect = $this->getFieldsToSelect($originalQueryParts);
        }
        if ($originalQueryParts['join']) {
            $this->addJoins($optimizedQueryBuilder, $originalQueryParts, $this->useNonSymmetricJoins($fieldsToSelect));
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
     * @param QueryBuilder $qb
     * @param array $usedAliases
     * @param string $having
     * @return string
     */
    private function prepareHavingClause(QueryBuilder $qb, array $usedAliases, $having)
    {
        $platform = $qb->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $fields = $this->qbTools->getFieldsWithoutAggregateFunctions($having);

            foreach ($fields as $field) {
                if (isset($usedAliases[$field])) {
                    $having = str_replace($field, $usedAliases[$field], $having);
                }
            }
        }

        return $having;
    }

    /**
     * Method to check if using of non symmetric joins is required (if they will affect number of rows or not).
     *
     * @param array $fieldsToSelect
     *
     * @return bool
     */
    protected function useNonSymmetricJoins(array $fieldsToSelect)
    {
        return count($fieldsToSelect) !== 1 || stripos(reset($fieldsToSelect), 'DISTINCT(') !== 0;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $optimizedQueryBuilder
     * @param array        $originalQueryParts
     * @param bool         $useNonSymmetricJoins
     */
    protected function addJoins(
        QueryBuilder $optimizedQueryBuilder,
        array $originalQueryParts,
        $useNonSymmetricJoins = true
    ) {
        // Collect list of tables which should be added to new query
        $whereAliases   = $this->qbTools->getUsedTableAliases($originalQueryParts['where']);
        $groupByAliases = $this->qbTools->getUsedTableAliases($originalQueryParts['groupBy']);
        $havingAliases  = $this->qbTools->getUsedTableAliases($originalQueryParts['having']);
        $joinAliases    = array_merge($whereAliases, $groupByAliases, $havingAliases);
        $joinAliases    = array_unique($joinAliases);
        $fromQueryPart = $originalQueryParts['from'];
        $joinQueryPart = $originalQueryParts['join'];

        // this joins cannot be removed outside of this class
        $requiredJoinAliases = $joinAliases;

        $rootAliases = array_map(
            function ($from) {
                return $from->getAlias();
            },
            $fromQueryPart
        );

        $joinAliases = $this->addRequiredJoins(
            $joinAliases,
            $fromQueryPart,
            $joinQueryPart,
            $groupByAliases,
            $useNonSymmetricJoins
        );

        $requiredJoinAliases = array_intersect(
            array_diff($requiredJoinAliases, $rootAliases),
            $this->context->getAliases()
        );

        $collectedAliases = $joinAliases;
        $joinAliases = $this->dispatchQueryOptimizationEvent($joinAliases, $requiredJoinAliases);

        // additional check joins after the events in case if events change joins list
        if (count($joinAliases) !== count($collectedAliases)) {
            $joinAliases = $this->addRequiredJoins(
                $requiredJoinAliases,
                $fromQueryPart,
                $this->removeAliasesFromQueryJoinParts($joinQueryPart, $joinAliases),
                $groupByAliases,
                $useNonSymmetricJoins
            );
            $relationJoinAliases = $this->addRequiredJoins(
                $requiredJoinAliases,
                $fromQueryPart,
                $this->removeAliasesFromQueryJoinParts($joinQueryPart, $collectedAliases),
                $groupByAliases,
                false
            );

            $joinAliases = array_unique(array_merge($joinAliases, $relationJoinAliases));
        }

        foreach ($rootAliases as $rootAlias) {
            if (!isset($joinQueryPart[$rootAlias])) {
                continue;
            }
            /** @var Expr\Join $join */
            foreach ($joinQueryPart[$rootAlias] as $join) {
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

        if (array_key_exists($associationName, $associations)) {
            return $associations[$associationName]['type'];
        }

        if ($this->relationHelper && $this->relationHelper->hasVirtualRelations($entityClass)) {
            return $this->relationHelper->getMetadataTypeForVirtualJoin($entityClass, $targetEntityClass);
        }

        return 0;
    }

    /**
     * Get SELECT fields for optimized Query Builder.
     *
     * By default all identifier fields are added.
     * If some of them are selected as DISTINCT in original Query Builder,
     * then them also should be selected as DISTINCT in optimized Query Builder.
     *
     * @param array $originalQueryParts
     *
     * @return array
     */
    protected function getFieldsToSelect(array $originalQueryParts)
    {
        $fieldsToSelect = [];
        $distinctField = null;
        /** @var Expr\Select $originalSelect */
        foreach ($originalQueryParts['select'] as $originalSelect) {
            foreach ($originalSelect->getParts() as $part) {
                $selectField = (string)$part;
                if (stripos($selectField, 'distinct') === 0) {
                    $distinctField = strtolower($selectField);
                    $fieldsToSelect[] = $selectField;
                    break 2;
                }
            }
        }

        /** @var Expr\From $from */
        foreach ($originalQueryParts['from'] as $from) {
            $alias = $from->getAlias();
            foreach ($this->context->getClassMetadata($from->getFrom())->getIdentifierFieldNames() as $item) {
                $fieldName = $alias . '.' . $item;
                if (stripos($distinctField, $fieldName) === false) {
                    $fieldsToSelect[] = $fieldName;
                }
            }
        }

        return $fieldsToSelect;
    }

    /**
     * Removes list of aliases from original query parts
     *
     * @param array $originalQueryParts
     * @param array $joinAliases
     *
     * @return array
     */
    protected function removeAliasesFromQueryJoinParts(array $originalQueryParts, array $joinAliases)
    {
        $filteredJoins = [];
        foreach ($originalQueryParts as $root => $joins) {
            foreach ($joins as $declaration) {
                /** @var Expr\Join $name */
                $name = $declaration->getAlias();
                if (in_array($name, $joinAliases)) {
                    if (!array_key_exists($root, $filteredJoins)) {
                        $filteredJoins[$root] = [];
                    }
                    $filteredJoins[$root][] = $declaration;
                }
            }
        }

        return $filteredJoins;
    }

    /**
     * Collect and add the joins should be added to query
     *
     * @param array   $joinAliases
     * @param array   $fromQueryPart
     * @param array   $joinQueryPart
     * @param array   $groupByAliases
     * @param boolean $useNonSymmetricJoins
     *
     * @return array
     */
    protected function addRequiredJoins(
        array $joinAliases,
        array $fromQueryPart,
        array $joinQueryPart,
        array $groupByAliases,
        $useNonSymmetricJoins
    ) {
        if ($useNonSymmetricJoins) {
            $joinAliases = array_merge(
                $joinAliases,
                $this->getNonSymmetricJoinAliases(
                    $fromQueryPart,
                    $joinQueryPart,
                    $groupByAliases
                )
            );
        }

        $rootAliases = [];
        /** @var Expr\From $from */
        foreach ($fromQueryPart as $from) {
            $rootAliases[] = $from->getAlias();
            $joinAliases = array_merge(
                $joinAliases,
                $this->qbTools->getUsedJoinAliases($joinQueryPart, $joinAliases, $from->getAlias())
            );
        }

        $allAliases = $this->context->getAliases();
        $joinAliases = array_intersect(array_diff(array_unique($joinAliases), $rootAliases), $allAliases);

        return $joinAliases;
    }
}
