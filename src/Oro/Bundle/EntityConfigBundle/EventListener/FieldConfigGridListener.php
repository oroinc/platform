<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class FieldConfigGridListener extends AbstractConfigGridListener
{
    const ENTITY_PARAM = 'entityId';

    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $this->parameters = $datagrid->getParameters();
            $queryBuilder = $datasource->getQueryBuilder();
            $this->prepareQuery($queryBuilder, 'cf', 'cfv_', PropertyConfigContainer::TYPE_FIELD);
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        // false flag used to place dynamic columns to the end of grid
        $this->doBuildBefore($event, 'cfv_', PropertyConfigContainer::TYPE_FIELD, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQuery(QueryBuilder $query, $rootAlias, $alias, $itemsType)
    {
        $query->setParameter('entity_id', $this->parameters->get(self::ENTITY_PARAM, 0));

        return parent::prepareQuery($query, $rootAlias, $alias, $itemsType);
    }
}
