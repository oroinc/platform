<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterInterface;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter;

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
        $filters = isset($queryFilter['definition']['filters'])
            ? $queryFilter['definition']['filters']
            : [];

        $rootEntity = isset($queryFilter['entity']) ? $queryFilter['entity'] : null;
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
        $this->rootEntityAlias          = $rootEntityAlias;
        $this->definition['filters']    = $filters;
        $this->definition['columns']    = [];
        $this->qb                       = $qb;
        $this->joinIdHelper             = new JoinIdentifierHelper($this->getRootEntity());
        $this->joins                    = [];
        $this->tableAliases             = [];
        $this->columnAliases            = [];
        $this->virtualColumnExpressions = [];
        $this->virtualColumnOptions     = [];
        $this->filters                  = [];
        $this->currentFilterPath        = '';
        $this->buildQuery();
        $this->virtualColumnOptions     = null;
        $this->virtualColumnExpressions = null;
        $this->columnAliases            = null;
        $this->tableAliases             = null;
        $this->joins                    = null;
        $this->joinIdHelper             = null;

        return $this->qb;
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
        $joinId                              = self::ROOT_ALIAS_KEY;
        $this->tableAliases[$joinId]         = $this->rootEntityAlias;
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
            } else {
                throw new \RuntimeException(
                    'Cannot get root alias. A query builder has more than one root entity.'
                );
            }
        }

        return $aliases[0];
    }
}
