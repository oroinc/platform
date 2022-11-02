<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\Model\ExpressionBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\Restriction;

/**
 * The adapter to an ORM data source which allows to combine restrictions in groups,
 * thus it allows to specify priority of restrictions.
 */
class GroupingOrmFilterDatasourceAdapter extends OrmFilterDatasourceAdapter
{
    /** @var ExpressionBuilder */
    private $expressionBuilder;

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

    /**
     * Starts a new restriction group.
     *
     * @param string $condition
     */
    public function beginRestrictionGroup($condition)
    {
        $this->expressionBuilder->beginGroup($condition);
    }

    /**
     * Ends a restriction group previously added by {@see addRestriction} method.
     */
    public function endRestrictionGroup()
    {
        $this->expressionBuilder->endGroup();
    }

    /**
     * Applies all restrictions previously added by {@see addRestriction} method.
     */
    public function applyRestrictions()
    {
        $this->expressionBuilder->applyRestrictions($this->qb);
    }
}
