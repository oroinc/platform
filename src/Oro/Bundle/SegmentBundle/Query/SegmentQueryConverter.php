<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\SegmentBundle\Model\SegmentIdentityAwareInterface;

/**
 * Converts a segment query definition created by the query designer to an ORM query.
 */
class SegmentQueryConverter extends GroupingOrmQueryConverter
{
    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /** @var SegmentQueryConverterState|null */
    private $state;

    /** @var QueryBuilder */
    protected $qb;

    /** @var string */
    private $aliasPrefix;

    /**
     * @param FunctionProviderInterface        $functionProvider
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     * @param ManagerRegistry                  $doctrine
     * @param RestrictionBuilderInterface      $restrictionBuilder
     * @param SegmentQueryConverterState|null  $state
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilderInterface $restrictionBuilder,
        SegmentQueryConverterState $state = null
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $virtualRelationProvider, $doctrine);
        $this->restrictionBuilder = $restrictionBuilder;
        $this->state = $state;
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
     * @param AbstractQueryDesigner $source
     *
     * @return QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source): QueryBuilder
    {
        if (null === $this->state) {
            return $this->convertToQueryBuilder($source);
        }

        $segmentId = $this->getSegmentId($source);
        if (!$segmentId) {
            return $this->convertToQueryBuilder($source);
        }

        $this->state->registerQuery($segmentId);
        try {
            // the cache can be used only for a root segment query (not a filter),
            // otherwise table alias conflicts may occur
            if ($this->state->isRootQuery($segmentId)) {
                $qb = $this->state->getQueryFromCache($segmentId);
                if (null !== $qb) {
                    return $qb;
                }
            }

            $this->aliasPrefix = $this->state->buildQueryAlias($segmentId, $source);
            $qb = $this->convertToQueryBuilder($source);
            $this->state->saveQueryToCache($segmentId, $qb);

            return $qb;
        } finally {
            $this->state->unregisterQuery($segmentId);
        }
    }

    /**
     * @param AbstractQueryDesigner $source
     *
     * @return QueryBuilder
     */
    protected function convertToQueryBuilder(AbstractQueryDesigner $source): QueryBuilder
    {
        $qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();
        $this->qb = $qb;
        $this->doConvert($source);

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    protected function resetConvertState(): void
    {
        parent::resetConvertState();
        $this->qb = null;
        $this->aliasPrefix = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateTableAlias()
    {
        $tableAlias = parent::generateTableAlias();
        if ($this->aliasPrefix) {
            $tableAlias .= '_' . $this->aliasPrefix;
        }

        return $tableAlias;
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
        if ($functionExpr !== null) {
            $functionExpr = $this->prepareFunctionExpression(
                $functionExpr,
                $tableAlias,
                $fieldName,
                $columnExpr,
                $columnAlias
            );
        }

        // column aliases are not used here, because of parser error
        $this->qb->addSelect($functionExpr ?? $columnExpr);
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
        if ($this->columnAliases && $columnAlias) {
            $columnNames = array_flip($this->columnAliases);
            $columnName = $columnNames[$columnAlias];
            $prefixedColumnName = $this->getPrefixedColumnName($columnName);
            $this->qb->addOrderBy($prefixedColumnName, $columnSorting);
        }
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getPrefixedColumnName($columnName)
    {
        $joinId = $this->joinIdHelper->buildColumnJoinIdentifier($columnName);
        if (array_key_exists($joinId, $this->virtualColumnOptions)
            && array_key_exists($columnName, $this->virtualColumnExpressions)
        ) {
            return $this->virtualColumnExpressions[$columnName];
        }

        return $this->getTableAliasForColumn($columnName) . '.' . $columnName;
    }

    /**
     * @param AbstractQueryDesigner $source
     *
     * @return int|null
     */
    private function getSegmentId(AbstractQueryDesigner $source): ?int
    {
        return $source instanceof SegmentIdentityAwareInterface
            ? $source->getSegmentId()
            : null;
    }
}
