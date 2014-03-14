<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
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

    /** @var EntityManager */
    protected $em;

    /** @var RestrictionBuilder */
    protected $restrictionBuilder;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     * @param RestrictionBuilder        $restrictionBuilder
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilder $restrictionBuilder
    ) {
        $this->em                 = $doctrine->getManager();
        $this->restrictionBuilder = $restrictionBuilder;
        parent::__construct($functionProvider, $doctrine);
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
        $this->qb = $this->em->createQueryBuilder();
        $this->doConvert($source);

        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType
    ) {
        $columnName = sprintf('%s.%s', $tableAlias, $fieldName);
        if ($functionExpr !== null) {
            $functionExpr = $this->prepareFunctionExpression(
                $functionExpr,
                $tableAlias,
                $fieldName,
                $columnName,
                $columnAlias
            );
        }

        // @TODO find solution for aliases
        // column aliases are not used here, because of parser error
        $select = $functionExpr !== null ? $functionExpr : $columnName;
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
    protected function addJoinStatement($joinTableAlias, $joinFieldName, $joinAlias)
    {
        if ($this->isInnerJoin($joinAlias, $joinFieldName)) {
            $this->qb->innerJoin(sprintf('%s.%s', $joinTableAlias, $joinFieldName), $joinAlias);
        } else {
            $this->qb->leftJoin(sprintf('%s.%s', $joinTableAlias, $joinFieldName), $joinAlias);
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
    protected function addGroupByColumn($tableAlias, $fieldName)
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
