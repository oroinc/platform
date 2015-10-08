<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\QueryBuilder;

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
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
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
            $this->buildCountQueryBuilder();
        } catch (\Exception $e) {
            // make sure that a link to the context is removed even if an exception occurred
            $this->context = null;
            // rethrow an exception
            throw $e;
        }

        $qb = $this->context->getOptimizedQueryBuilder();

        // remove a link to the context
        $this->context = null;

        return $qb;
    }

    protected function buildCountQueryBuilder()
    {
        $optimizedQueryBuilder = $this->context->getOptimizedQueryBuilder();
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
        $requiredToJoin = $this->qbTools->getUsedTableAliases($originalQueryParts['where']);
        $groupByAliases = $this->qbTools->getUsedTableAliases($originalQueryParts['groupBy']);
        $requiredToJoin = array_merge($requiredToJoin, $groupByAliases);
        $requiredToJoin = array_merge(
            $requiredToJoin,
            $this->qbTools->getUsedTableAliases($originalQueryParts['having'])
        );
        $requiredToJoin = array_merge(
            $requiredToJoin,
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
            $requiredToJoin = array_merge(
                $requiredToJoin,
                $this->qbTools->getUsedJoinAliases($originalQueryParts['join'], $requiredToJoin, $from->getAlias())
            );
        }

        $requiredToJoin = array_diff(array_unique($requiredToJoin), $rootAliases);

        foreach ($rootAliases as $rootAlias) {
            if (!isset($originalQueryParts['join'][$rootAlias])) {
                continue;
            }
            /** @var Expr\Join $join */
            foreach ($originalQueryParts['join'][$rootAlias] as $join) {
                $alias = $join->getAlias();
                // To count results number join all tables with inner join and required to tables
                if ($join->getJoinType() === Expr\Join::INNER_JOIN || in_array($alias, $requiredToJoin, true)) {
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
     * @param GroupBy[] $groupBy
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

        $aliasToJoinExpr = $this->getAliasToJoinExprMap($fromStatements, $joins);
        $dependencies    = $this->getNonSymmetricJoinDependencies($fromStatements, $joins);
        foreach ($dependencies as $alias => $joinInfo) {
            $join = $this->getJoinByAlias($joins, $alias);

            // skip joins that is not left joins or was not used in GROUP BY statement
            // if it was a GROUP BY statement at all
            if ($join->getJoinType() !== 'LEFT'
                || (!empty($groupByAliases) && !in_array($alias, $groupByAliases, true))
            ) {
                continue;
            }

            // if there is just association expression like `a.owner`
            $parts = explode('.', $join->getJoin());
            if (count($parts) === 2) {
                list($associationAlias, $associationName) = $parts;
                $className       = $this->resolveEntityClassByAlias(
                    $associationAlias,
                    $associationName,
                    $aliasToJoinExpr
                );
                $associationType = $this->getAssociationType($className, $associationName);
                if ($associationType & ClassMetadata::TO_MANY) {
                    $aliases[] = $alias;
                }
            } else { // otherwise there is a class name
                $joinEntityClass = $this->resolveEntityClass($join->getJoin());

                $aliasToAssociation = []; // [alias => associationName, ...]
                foreach ($this->qbTools->getFields($join->getCondition()) as $field) {
                    list($associationAlias, $associationName) = explode('.', $field);
                    $aliasToAssociation[$associationAlias] = $associationName;
                }

                // for each alias the current join is depended (this alias is called as $dependeeAlias):
                // - resolve it's entity class
                // - check the relations
                foreach ($joinInfo[1] as $dependeeAlias) {
                    $associationName = array_key_exists($dependeeAlias, $aliasToAssociation)
                        ? $aliasToAssociation[$dependeeAlias]
                        : $aliasToAssociation[$alias];
                    $dependeeClass   = $this->resolveEntityClassByAlias(
                        $dependeeAlias,
                        $associationName,
                        $aliasToJoinExpr
                    );
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
     * Gets the type of the given association
     *
     * @param string      $entityClass
     * @param string      $associationName
     * @param string|null $targetEntityClass
     *
     * @return int
     */
    protected function getAssociationType($entityClass, $associationName, $targetEntityClass = null)
    {
        $metadata = $this->context->getClassMetadata($entityClass);
        if (!$targetEntityClass) {
            $associationMapping = $metadata->getAssociationMapping($associationName);

            return $associationMapping['type'];
        }

        $associations = $metadata->getAssociationsByTargetClass($targetEntityClass);
        if (!array_key_exists($associationName, $associations)) {
            return 0;
        }

        return $associations[$associationName]['type'];
    }

    /**
     * Collects the correspondence of select alias to it's join expression
     *
     * @param Expr\From[] $fromStatements
     * @param array       $joins
     *
     * @return array [alias => joinExpr, ...]
     */
    protected function getAliasToJoinExprMap($fromStatements, $joins)
    {
        $result = [];
        foreach ($fromStatements as $from) {
            $result[$from->getAlias()] = $this->resolveEntityClass($from->getFrom());
        }
        /* @var Expr\Join[] $childJoins */
        foreach ($joins as $rootAlias => $childJoins) {
            foreach ($childJoins as $join) {
                $result[$join->getAlias()] = $this->resolveEntityClass($join->getJoin());
            }
        }

        return $result;
    }

    /**
     * Finds a join statement that corresponds to current alias
     *
     * @param array  $joins
     * @param string $alias
     *
     * @return Expr\Join
     *
     * @throws \InvalidArgumentException if a requested join cannot be found
     */
    protected function getJoinByAlias($joins, $alias)
    {
        /* @var Expr\Join[] $childJoins */
        foreach ($joins as $rootAlias => $childJoins) {
            foreach ($childJoins as $join) {
                if ($join->getAlias() === $alias) {
                    return $join;
                }
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'The join alias "%s" was not found. Existing aliases: %s.',
                $alias,
                implode(
                    ', ',
                    array_map(
                        function ($join) {
                            /** @var Expr\Join $join */
                            return $join->getAlias();
                        },
                        $joins
                    )
                )
            )
        );
    }

    /**
     * Gets the full class name if the given join represents an entity name in form "bundle:class"
     *
     * @param string $join
     *
     * @return string
     */
    protected function resolveEntityClass($join)
    {
        $parts = explode(':', $join);
        if (count($parts) === 2) {
            $join = $this->context->getOriginalQueryBuilder()
                    ->getEntityManager()
                    ->getConfiguration()
                    ->getEntityNamespace($parts[0]) . '\\' . $parts[1];
        }

        return $join;
    }

    /**
     * Resolve the FQCN by it's alias and association name
     *
     * @param string   $alias
     * @param string   $associationName
     * @param string[] $aliasToJoinExpr [alias => joinExpr, ...]
     *
     * @return string
     */
    protected function resolveEntityClassByAlias($alias, $associationName, $aliasToJoinExpr)
    {
        $expr = $aliasToJoinExpr[$alias];

        $exprParts = explode('.', $expr);
        if (count($exprParts) === 2) {
            list($exprAlias, $exprAssocName) = $exprParts;

            $expr = $aliasToJoinExpr[$exprAlias];
            if (count(explode('.', $expr)) === 2) {
                $expr = $this->resolveEntityClassByAlias($exprAlias, $exprAssocName, $aliasToJoinExpr);
            }

            $associationMapping = $this->context->getClassMetadata($expr)
                ->getAssociationMapping($exprAssocName);

            $expr = $associationMapping['sourceEntity'];
            if (!$this->context->getClassMetadata($expr)->hasAssociation($associationName)) {
                $expr = $associationMapping['targetEntity'];
            }
        }

        return $expr;
    }
}
