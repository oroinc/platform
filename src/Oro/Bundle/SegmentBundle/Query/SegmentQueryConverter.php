<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

class SegmentQueryConverter extends GroupingOrmQueryConverter
{
    /*
     * Override to prevent naming conflicts
     */
    const COLUMN_ALIAS_TEMPLATE = 'cs%d';
    const TABLE_ALIAS_TEMPLATE  = 'ts%d';

    /** @var QueryBuilder */
    protected $qb;

    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     * @param RestrictionBuilderInterface   $restrictionBuilder
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilderInterface $restrictionBuilder
    ) {
        $this->restrictionBuilder = $restrictionBuilder;
        parent::__construct($functionProvider, $virtualFieldProvider, $doctrine);
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        // nothing to do
    }

    /**
     * Process convert
     *
     * @param AbstractQueryDesigner $source
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source)
    {
        $this->qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();
        $this->doConvert($source);

        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateTableAlias($offset = 1)
    {
        return sprintf(static::TABLE_ALIAS_TEMPLATE, mt_rand());
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType,
        $isDistinct = false
    ) {
        if ($isDistinct) {
            $columnExpr = 'DISTINCT ' . (string)$columnExpr;
        }

        if ($functionExpr !== null) {
            $functionExpr = $this->prepareFunctionExpression(
                $functionExpr,
                $tableAlias,
                $fieldName,
                $columnExpr,
                $columnAlias
            );
        }

        // @TODO find solution for aliases before generalizing this converter
        // column aliases are not used here, because of parser error
        $select = $functionExpr !== null ? $functionExpr : $columnExpr;
        $this->qb->addSelect($select);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement($entityClassName, $tableAlias)
    {
        $this->qb->from($entityClassName, $tableAlias);
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition)
    {
        if (self::LEFT_JOIN === $joinType) {
            $this->qb->leftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->qb->innerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement()
    {
        parent::addWhereStatement();
        if (!empty($this->filters)) {
            $this->restrictionBuilder->buildRestrictions(
                $this->filters,
                new GroupingOrmFilterDatasourceAdapter($this->qb)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn($columnAlias)
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        // do nothing, order could not change results
    }
}
