<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * Field Configuration Grid Listener that adds entity_id to query and removes records that relates to hidden entities
 */
class FieldConfigGridListener extends AbstractConfigGridListener
{
    public const ENTITY_PARAM = 'entityId';

    /**
     * @var ParameterBag
     */
    protected $parameters;

    #[\Override]
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

    #[\Override]
    public function onBuildBefore(BuildBefore $event)
    {
        // false flag used to place dynamic columns to the end of grid
        $this->doBuildBefore($event, 'cfv_', PropertyConfigContainer::TYPE_FIELD, false);
    }

    public function onOrmResultAfter(OrmResultAfter $event)
    {
        $records = $event->getRecords();
        $configProvider = $this->configManager->getProvider('extend');

        foreach ($records as $key => $record) {
            $className = $record->getValue('className');
            $fieldName = $record->getValue('fieldName');
            $targetEntity = null;

            if (!is_string($className) || !is_string($fieldName)) {
                continue;
            }

            if ($configProvider->hasConfig($className, $fieldName)) {
                $targetEntity = $configProvider->getConfig($className, $fieldName)->get('target_entity');
            }

            if ($targetEntity && $this->configManager->isHiddenModel($targetEntity)) {
                unset($records[$key]);
            }
        }

        $event->setRecords(array_values($records));
    }

    #[\Override]
    protected function prepareQuery(QueryBuilder $query, $rootAlias, $alias, $itemsType)
    {
        $query->setParameter('entity_id', $this->parameters->get(self::ENTITY_PARAM, 0));

        return parent::prepareQuery($query, $rootAlias, $alias, $itemsType);
    }
}
