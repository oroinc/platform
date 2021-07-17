<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterInterface;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a functionality to apply a segment filters to a widget query.
 */
class FilterProcessor extends SegmentQueryConverter implements WidgetProviderFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions): void
    {
        $queryFilter = $widgetOptions->get('queryFilter', []);
        $filters = $queryFilter['definition']['filters'] ?? [];

        $rootEntity = $queryFilter['entity'] ?? null;
        if (!$rootEntity) {
            return;
        }
        $rootEntityAlias = QueryBuilderUtil::getSingleRootAlias($queryBuilder);

        $this->process($queryBuilder, $rootEntity, $filters, $rootEntityAlias);
    }

    public function process(
        QueryBuilder $qb,
        string $rootEntity,
        array $filters,
        string $rootEntityAlias
    ): QueryBuilder {
        $filters = array_filter($filters);
        if (!$filters) {
            // nothing to do
            return $qb;
        }

        $source = new QueryDesigner(
            $rootEntity,
            $this->encodeDefinition(['filters' => $filters])
        );

        $this->context()->setRootEntityAlias($rootEntityAlias);
        $this->doConvertToQueryBuilder($source, $qb);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext(): FilterProcessorContext
    {
        return new FilterProcessorContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function context(): FilterProcessorContext
    {
        return parent::context();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTableAliases(): void
    {
        $this->addTableAliasForRootEntity();
        $definition = $this->context()->getDefinition();
        if (isset($definition['filters'])) {
            $this->addTableAliasesForFilters($definition['filters']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addTableAliasForRootEntity(): void
    {
        $this->context()->setRootTableAlias($this->context()->getRootEntityAlias());
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareColumnAliases(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectStatement(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatements(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByStatement(): void
    {
        // nothing to do
    }
}
