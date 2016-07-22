<?php
namespace Oro\Bundle\DashboardBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter;

class FilterQueryProcessor extends SegmentQueryConverter
{
    protected $rootEntityAlias;

    public function process(QueryBuilder $qb, $rootEntity, array $filters, $rootEntityAlias)
    {
        $this->setRootEntity($rootEntity);
        $this->rootEntityAlias = $rootEntityAlias;
        $this->definition['filters'] = $filters;
        $this->definition['columns'] = [];
        $this->qb = $qb;
        $this->joinIdHelper             = new JoinIdentifierHelper($this->getRootEntity());
        $this->joins                    = [];
        $this->tableAliases             = [];
        $this->columnAliases            = [];
        $this->virtualColumnExpressions = [];
        $this->virtualColumnOptions     = [];
        $this->buildQuery();
        $this->virtualColumnOptions     = null;
        $this->virtualColumnExpressions = null;
        $this->columnAliases            = null;
        $this->tableAliases             = null;
        $this->joins                    = null;
        $this->joinIdHelper             = null;
        return $this->qb;
    }

    protected function buildQuery()
    {
        $this->prepareTableAliases();
        $this->addJoinStatements();
        $this->addWhereStatement();
        $this->addGroupByStatement();
    }

    protected function prepareTableAliases()
    {
        $this->addTableAliasForRootEntity();
        if (isset($this->definition['filters'])) {
            $this->addTableAliasesForFilters($this->definition['filters']);
        }
    }

    protected function addTableAliasForRootEntity()
    {
        $joinId = self::ROOT_ALIAS_KEY;
        $this->tableAliases[$joinId] = $this->rootEntityAlias;
        $this->joins[$this->rootEntityAlias] = $joinId;
    }

    protected function prepareColumnAliases()
    {
    }

    protected function addSelectStatement()
    {
    }

    protected function addFromStatements()
    {
    }
}
