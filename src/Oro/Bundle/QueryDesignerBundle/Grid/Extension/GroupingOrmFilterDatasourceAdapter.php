<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid\Extension;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\Model\ExpressionBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\Restriction;

/**
 * Represents ORM data source adapter which allows to combine restrictions in groups,
 * thus it allows to specify priority of restrictions
 */
class GroupingOrmFilterDatasourceAdapter extends OrmFilterDatasourceAdapter
{
    /** @var ExpressionBuilder */
    protected $expressionBuilder;

    /**
     * Constructor
     *
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        parent::__construct($qb);
        $this->expressionBuilder = new ExpressionBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function addRestriction($restriction, $condition, $isComputed = false)
    {
        $this->expressionBuilder->addRestriction(new Restriction($restriction, $condition, $isComputed));
    }

    public function beginRestrictionGroup($condition)
    {
        $this->expressionBuilder->beginGroup($condition);
    }

    public function endRestrictionGroup()
    {
        $this->expressionBuilder->endGroup();
    }

    /**
     * Applies all restrictions previously added using addRestriction and addRestrictionOperator methods
     */
    public function applyRestrictions()
    {
        $this->expressionBuilder->applyRestrictions($this->qb);
    }
}
