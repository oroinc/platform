<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\SegmentBundle\Model\SegmentIdentityAwareInterface;

/**
 * Converts a segment query definition created by the query designer to an ORM query.
 */
class SegmentQueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    /** @var RestrictionBuilderInterface */
    private $restrictionBuilder;

    /** @var SegmentQueryConverterState|null */
    private $state;

    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        DoctrineHelper $doctrineHelper,
        RestrictionBuilderInterface $restrictionBuilder,
        SegmentQueryConverterState $state = null
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $virtualRelationProvider, $doctrineHelper);
        $this->restrictionBuilder = $restrictionBuilder;
        $this->state = $state;
    }

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

            $this->context()->setAliasPrefix($this->state->buildQueryAlias($segmentId, $source));
            $qb = $this->convertToQueryBuilder($source);
            $this->state->saveQueryToCache($segmentId, $qb);

            return $qb;
        } finally {
            $this->state->unregisterQuery($segmentId);
        }
    }

    protected function convertToQueryBuilder(AbstractQueryDesigner $source): QueryBuilder
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass($source->getEntity())->createQueryBuilder();
        $this->doConvertToQueryBuilder($source, $qb);

        return $qb;
    }

    protected function doConvertToQueryBuilder(AbstractQueryDesigner $source, QueryBuilder $qb): void
    {
        $this->context()->setQueryBuilder($qb);
        $this->doConvert($source);
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext(): SegmentQueryConverterContext
    {
        return new SegmentQueryConverterContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function context(): SegmentQueryConverterContext
    {
        return parent::context();
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases(array $tableAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases(array $columnAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void {
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
        $this->context()->getQueryBuilder()->addSelect($functionExpr ?? $columnExpr);
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement(): void
    {
        parent::addWhereStatement();
        $filters = $this->context()->getFilters();
        if ($filters) {
            $this->restrictionBuilder->buildRestrictions(
                $filters,
                new GroupingOrmFilterDatasourceAdapter($this->context()->getQueryBuilder())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByStatement(): void
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn(string $columnAlias): void
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        $this->context()->getQueryBuilder()->addOrderBy(
            $this->getOrderByColumnExpr($columnAlias),
            $columnSorting
        );
    }

    private function getOrderByColumnExpr(string $columnAlias): string
    {
        $columnName = $this->context()->getColumnName($this->context()->getColumnId($columnAlias));
        $columnJoinId = $this->buildColumnJoinIdentifier($columnName);
        if ($this->context()->hasVirtualColumnExpression($columnName)
            && $this->context()->hasVirtualColumnOptions($columnJoinId)
        ) {
            return $this->context()->getVirtualColumnExpression($columnName);
        }

        return $this->getTableAliasForColumn($columnName) . '.' . $columnName;
    }

    private function getSegmentId(AbstractQueryDesigner $source): ?int
    {
        return $source instanceof SegmentIdentityAwareInterface
            ? $source->getSegmentId()
            : null;
    }
}
