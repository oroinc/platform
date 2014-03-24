<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr;
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
            ->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('where')
            ->resetDQLPart('having');

        $this->qbTools->prepareFieldAliases($parts['select']);
        $fieldsToSelect = array($this->getFieldFQN($this->idFieldName));
        $usedAliases = array();
        if ($parts['groupBy']) {
            $usedAliases = array_merge($usedAliases, $this->qbTools->getUsedAliases((array) $parts['groupBy']));
        } elseif (!$parts['where'] && $parts['having']) {
            // If there is no where and group by, but having is present - convert having to where.
            $parts['where'] = $parts['having'];
            $parts['having'] = null;
            $qb->resetDQLPart('having');
        }

        if ($parts['having']) {
            $usedAliases = array_merge($usedAliases, $this->qbTools->getUsedAliases($parts['having']));
            $parts['having'] = $this->qbTools->fixHavingAliases($parts['having']);
            $qb->having($parts['having']);
        }

        $hasJoins = false;
        if ($parts['join']) {
            $hasJoins = $this->addJoins($qb, $parts);
        }

        // Add group by fields to select fields.
        foreach ($usedAliases as $alias) {
            $fieldsToSelect[] = $this->qbTools->getFieldByAlias($alias) . ' as ' . $alias;
        }
        if ($parts['having']) {
            $fieldsToSelect = $this->appendFieldsToSelect(
                $this->qbTools->getFields($parts['having']),
                $fieldsToSelect
            );
        }

        if ($parts['where']) {
            $qb->where($this->qbTools->replaceAliasesWithFields($parts['where']));
        }

        $qb->select($fieldsToSelect);
        $qb->distinct($hasJoins);
        $this->qbTools->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * @param array $fields
     * @param array $fieldsToSelect
     * @return array
     */
    protected function appendFieldsToSelect($fields, $fieldsToSelect)
    {
        $result = $fieldsToSelect;
        $select = implode(' ,', $fieldsToSelect) . ' ';
        $idx = 0;
        $prefix = '_havingField';
        foreach ($fields as $field) {
            if (stripos($select, $field . ' ') === false) {
                $alias = $prefix . $idx;
                $result[] = $field . ' as ' . $alias;
                $idx++;
            }
        }

        return $result;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $qb
     * @param array $parts
     * @return bool
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
        $requiredToJoin = array_diff(array_unique($requiredToJoin), array($this->rootAlias));

        /** @var Expr\Join $join */
        $hasJoins = false;
        foreach ($parts['join'][$this->rootAlias] as $join) {
            $alias = $join->getAlias();
            // To count results number join all tables with inner join and required to tables
            if ($join->getJoinType() == Expr\Join::INNER_JOIN || in_array($alias, $requiredToJoin)) {
                $hasJoins = true;
                $condition = $this->qbTools->replaceAliasesWithFields($join->getCondition());
                if ($join->getJoinType() == Expr\Join::INNER_JOIN) {
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

        return $hasJoins;
    }

    /**
     * Initialize the column id of the targeted class.
     *
     * @return string
     */
    protected function initIdFieldName()
    {
        /** @var $from \Doctrine\ORM\Query\Expr\From */
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
}
