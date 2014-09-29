<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class EntityRelationGridListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var Request */
    protected $request;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if ($request instanceof Request) {
            $this->request = $request;
        }
    }

    /**
     * @param BuildBefore $event
     * @return bool
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $datagrid = $event->getDatagrid();
        $config   = $event->getConfig();

        $entityClassName = $datagrid->getParameters()->get('class_name');
        $fieldName       = $datagrid->getParameters()->get('field_name');
        $entityId        = $datagrid->getParameters()->get('id');

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendFieldConfig    = $extendConfigProvider->getConfig($entityClassName, $fieldName);

        $targetEntityName = $extendFieldConfig->get('target_entity');
        $targetFieldNames = $extendFieldConfig->get('target_grid');

        // build 'assigned' field expression
        if ($entityId) {
            $extendEntityConfig = $extendConfigProvider->getConfig($entityClassName);
            $relations          = $extendEntityConfig->get('relation');
            $relation           = $relations[$extendFieldConfig->get('relation_key')];
            $targetFieldName    = $relation['target_field_id']->getFieldName();
            $fieldType          = $extendFieldConfig->getId()->getFieldType();
            $operator           = $fieldType == 'oneToMany' ? '=' : 'MEMBER OF';
            $whenExpr           = '(:relation ' . $operator . ' o.' . $targetFieldName . ' OR o.id IN (:data_in))'
                . ' AND o.id NOT IN (:data_not_in)';
        } else {
            $whenExpr = 'o.id IN (:data_in) AND o.id NOT IN (:data_not_in)';
        }
        $assignedExpr = "CASE WHEN " . $whenExpr . " THEN true ELSE false END";

        // build a query skeleton
        $query = [
            'select' => [
                'o.id',
                $assignedExpr . ' as assigned'
            ],
            'from'   => [
                ['table' => $targetEntityName, 'alias' => 'o']
            ]
        ];
        $config->offsetSetByPath('[source][query]', $query);

        // enable AdditionalFieldsExtension to add all other fields
        $config->offsetSetByPath('[options][entity_name]', $targetEntityName);
        $config->offsetSetByPath('[options][additional_fields]', $targetFieldNames);
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        /** @var OrmDatasource $datasource */
        $datasource = $datagrid->getDatasource();

        $entityId = $datagrid->getParameters()->get('id');

        if ($entityId) {
            $datasource->bindParameters(['relation']);
        }
    }
}
