<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\QueryBuilder;

class CountQueryBuilderOptimizer
{
    /** @var string */
    protected $idFieldName;

    /** @var string */
    protected $rootAlias;

    /** @var QueryBuilder */
    protected $originalQb;

    /** @var QueryBuilderTools */
    protected $qbTools;

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
     * Set original query builder.
     *
     * @param QueryBuilder $originalQb
     */
    protected function setOriginalQueryBuilder(QueryBuilder $originalQb)
    {
        $this->originalQb = $originalQb;

        $this->qbTools->prepareFieldAliases($originalQb->getDQLPart('select'));
        $this->qbTools->prepareJoinTablePaths($originalQb->getDQLPart('join'));
        $this->rootAlias = current($this->originalQb->getRootAliases());
        $this->initIdFieldName();
    }

    /**
     * Get optimized query builder for count calculation.
     *
     * @param QueryBuilder $originalQb
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(QueryBuilder $originalQb)
    {
        $this->setOriginalQueryBuilder($originalQb);
        $parts = $this->originalQb->getDQLParts();

        $qb = clone $this->originalQb;
        $qb->setFirstResult(null)
            ->setMaxResults(null)
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('where')
            ->resetDQLPart('having');

        $fieldsToSelect = array();
        if ($parts['groupBy']) {
            $groupBy = (array) $parts['groupBy'];
            $groupByFields = $this->getSelectFieldFromGroupBy($groupBy);
            $usedGroupByAliases = [];
            foreach ($groupByFields as $key => $groupByField) {
                $alias = '_groupByPart' . $key;
                $usedGroupByAliases[] = $alias;
                $fieldsToSelect[] = $groupByField . ' as ' . $alias;
            }
            $qb->groupBy(implode(', ', $usedGroupByAliases));
        } elseif (!$parts['where'] && $parts['having']) {
            // If there is no where and group by, but having is present - convert having to where.
            $parts['where'] = $parts['having'];
            $parts['having'] = null;
            $qb->resetDQLPart('having');
        }

        if ($parts['having']) {
            $qb->having(
                $this->qbTools->replaceAliasesWithFields($parts['having'])
            );
        }

        if ($parts['join']) {
            $this->addJoins($qb, $parts);
        }
        if (!$parts['groupBy']) {
            $fieldsToSelect[] = $this->getFieldFQN($this->idFieldName);
        }

        if ($parts['where']) {
            $qb->where($this->qbTools->replaceAliasesWithFields($parts['where']));
        }

        $qb->select(array_unique($fieldsToSelect));
        $this->qbTools->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $qb
     * @param array $parts
     */
    protected function addJoins(QueryBuilder $qb, array $parts)
    {
        // Collect list of tables which should be added to new query
        $requiredToJoin = $this->qbTools->getUsedTableAliases($parts['where']);
        $requiredToJoin = array_merge($requiredToJoin, $this->qbTools->getUsedTableAliases($parts['groupBy']));
        $requiredToJoin = array_merge($requiredToJoin, $this->qbTools->getUsedTableAliases($parts['having']));
        $requiredToJoin = array_merge(
            $requiredToJoin,
            $this->qbTools->getUsedJoinAliases($parts['join'], $requiredToJoin, $this->rootAlias)
        );
        $requiredToJoin = array_merge(
            $requiredToJoin,
            $this->getNonSymmetricJoinAliases($parts['join'], $parts['from'])
        );

        $requiredToJoin = array_diff(array_unique($requiredToJoin), array($this->rootAlias));

        /** @var Expr\Join $join */
        foreach ($parts['join'][$this->rootAlias] as $join) {
            $alias     = $join->getAlias();
            // To count results number join all tables with inner join and required to tables
            if ($join->getJoinType() === Expr\Join::INNER_JOIN || in_array($alias, $requiredToJoin, true)) {
                $condition = $this->qbTools->replaceAliasesWithFields($join->getCondition());
                $condition = $this->qbTools->replaceAliasesWithJoinPaths($condition);

                if ($join->getJoinType() === Expr\Join::INNER_JOIN) {
                    $qb->innerJoin(
                        $join->getJoin(),
                        $alias,
                        $join->getConditionType(),
                        $condition,
                        $join->getIndexBy()
                    );
                } else {
                    $qb->leftJoin(
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

    /**
     * Initialize the column id of the targeted class.
     *
     * @return string
     */
    protected function initIdFieldName()
    {
        /** @var Expr\From $from */
        $from = current($this->originalQb->getDQLPart('from'));
        $class = $from->getFrom();

        $idNames = $this->originalQb
            ->getEntityManager()
            ->getMetadataFactory()
            ->getMetadataFor($class)
            ->getIdentifierFieldNames();

        $this->idFieldName = current($idNames);
    }

    /**
     * Get fields fully qualified name
     *
     * @param string $fieldName
     * @return string
     */
    protected function getFieldFQN($fieldName)
    {
        if (strpos($fieldName, '.') === false) {
            $fieldName = $this->rootAlias . '.' . $fieldName;
        }

        return $fieldName;
    }

    /**
     * @param GroupBy[] $groupBy
     * @return array
     */
    protected function getSelectFieldFromGroupBy(array $groupBy)
    {
        $expressions = array();
        foreach ($groupBy as $groupByPart) {
            foreach ($groupByPart->getParts() as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        }

        return $expressions;
    }

    /**
     * @param string $groupByPart
     * @return array
     */
    protected function getSelectFieldFromGroupByPart($groupByPart)
    {
        $expressions = array();
        if (strpos($groupByPart, ',') !== false) {
            $groupByParts = explode(',', $groupByPart);
            foreach ($groupByParts as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        } else {
            $groupByPart = trim($groupByPart);
            $groupByPart = $this->qbTools->replaceAliasesWithFields($groupByPart);
            $expressions[] = $groupByPart;
        }

        return $expressions;
    }

    /**
     * Get join aliases that will produce non symmetric results
     * (many-to-one left join or one-to-many right join)
     *
     * @param array       $joins
     * @param Expr\From[] $fromStatements
     *
     * @return array
     */
    protected function getNonSymmetricJoinAliases($joins, $fromStatements)
    {
        $metadataFactory = $this->originalQb->getEntityManager()->getMetadataFactory();

        // make sure that metadata factory was initialized
        $metadataFactory->getAllMetadata();

        $aliasToClass = [];
        foreach ($fromStatements as $from) {
            /* @var Expr\From $from */
            $aliasToClass[$from->getAlias()] = $this->resolveEntityClass($from->getFrom());
        }
        foreach ($joins[$this->rootAlias] as $join) {
            /* @var Expr\Join $join */
            $aliasToClass[$join->getAlias()] = $this->resolveEntityClass($join->getJoin());
        }

        $aliases      = [];
        $dependencies = $this->qbTools->getAllDependencies($this->rootAlias, $joins[$this->rootAlias]);
        foreach ($dependencies as $alias => $joinInfo) {
            if ($metadataFactory->hasMetadataFor($aliasToClass[$alias])) {
                /** @var ClassMetadata $metadata */
                $metadata = $metadataFactory->getMetadataFor($aliasToClass[$alias]);
                foreach ($joinInfo[1] as $dependantAlias) {
                    $associations = $metadata->getAssociationsByTargetClass($aliasToClass[$dependantAlias]);
                    foreach ($associations as $name => $info) {
                        if (in_array($info['type'], [ClassMetadata::MANY_TO_ONE, ClassMetadata::MANY_TO_MANY])) {
                            $aliases[] = $alias;
                        }
                    }
                }
            }
        }

        return array_unique($aliases);
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
            $join = $this->originalQb
                ->getEntityManager()
                ->getConfiguration()
                ->getEntityNamespace($parts[0]) . '\\' . $parts[1];
        }

        return $join;
    }
}
