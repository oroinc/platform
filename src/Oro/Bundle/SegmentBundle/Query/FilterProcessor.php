<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterInterface;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;

/**
 * Provides a functionality to apply a segment filters to a widget query.
 */
class FilterProcessor extends SegmentQueryConverter implements WidgetProviderFilterInterface
{
    /** @var string */
    protected $rootEntityAlias;

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        $queryFilter = $widgetOptions->get('queryFilter', []);
        $filters = $queryFilter['definition']['filters'] ?? [];

        $rootEntity = $queryFilter['entity'] ?? null;
        if (!$rootEntity) {
            return;
        }
        $rootEntityAlias = $this->getRootAlias($queryBuilder);

        $this->process($queryBuilder, $rootEntity, $filters, $rootEntityAlias);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootEntity
     * @param array        $filters
     * @param string       $rootEntityAlias
     *
     * @return QueryBuilder
     */
    public function process(QueryBuilder $qb, $rootEntity, array $filters, $rootEntityAlias)
    {
        $filters = array_filter($filters);
        if (!$filters) {
            // nothing to do
            return $qb;
        }

        $this->setRootEntity($rootEntity);
        $this->rootEntityAlias = $rootEntityAlias;
        $this->joinIdHelper = new JoinIdentifierHelper($this->getRootEntity());
        $this->definition = ['filters' => $filters, 'columns' => []];
        $this->joins = [];
        $this->tableAliasesCount = 0;
        $this->tableAliases = [];
        $this->columnAliases = [];
        $this->aliases = [];
        $this->queryAliases = [];
        $this->virtualColumnExpressions = [];
        $this->virtualColumnOptions = [];
        $this->virtualRelationsJoins = [];
        $this->filters = [];
        $this->currentFilterPath = '';
        $this->qb = $qb;
        try {
            $this->buildQuery();
        } finally {
            $this->resetConvertState();
        }

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    protected function resetConvertState(): void
    {
        parent::resetConvertState();
        $this->rootEntityAlias = null;
        $this->qb = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildQuery()
    {
        $this->prepareTableAliases();
        $this->addJoinStatements();
        $this->addWhereStatement();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTableAliases()
    {
        $this->addTableAliasForRootEntity();
        if (isset($this->definition['filters'])) {
            $this->addTableAliasesForFilters($this->definition['filters']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addTableAliasForRootEntity()
    {
        $joinId = self::ROOT_ALIAS_KEY;
        $this->tableAliases[$joinId] = $this->rootEntityAlias;
        $this->joins[$this->rootEntityAlias] = $joinId;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getRootAlias(QueryBuilder $qb)
    {
        $aliases = $qb->getRootAliases();
        if (count($aliases) !== 1) {
            if (count($aliases) === 0) {
                throw new \RuntimeException(
                    'Cannot get root alias. A query builder has no root entity.'
                );
            }
            throw new \RuntimeException(
                'Cannot get root alias. A query builder has more than one root entity.'
            );
        }

        return $aliases[0];
    }
}
