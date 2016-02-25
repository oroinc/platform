<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class EntityRelationGridListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Request */
    protected $request;

    /**
     * @param ConfigManager  $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager  = $configManager;
        $this->doctrineHelper = $doctrineHelper;
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
     *
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
        $targetFieldNames = array_merge(
            $extendFieldConfig->get('target_grid'),
            $extendFieldConfig->get('target_title'),
            $extendFieldConfig->get('target_detailed')
        );
        $targetIdField    = $this->doctrineHelper->getSingleEntityIdentifierFieldName($targetEntityName);
        // build 'assigned' field expression
        if ($entityId) {
            $extendEntityConfig = $extendConfigProvider->getConfig($entityClassName);
            $relations          = $extendEntityConfig->get('relation');
            $relation           = $relations[$extendFieldConfig->get('relation_key')];
            $targetFieldName    = $relation['target_field_id']->getFieldName();
            $fieldType          = $extendFieldConfig->getId()->getFieldType();
            $operator           = $fieldType === RelationType::ONE_TO_MANY ? '=' : 'MEMBER OF';
            $whenExpr           = sprintf(
                '(:relation %s o.%s OR o.%s IN (:data_in)) AND o.%s NOT IN (:data_not_in)',
                $operator,
                $targetFieldName,
                $targetIdField,
                $targetIdField
            );
        } else {
            $whenExpr = sprintf('o.%s IN (:data_in) AND o.%s NOT IN (:data_not_in)', $targetIdField, $targetIdField);
        }
        $assignedExpr = 'CASE WHEN ' . $whenExpr . ' THEN true ELSE false END';

        // build a query skeleton
        $query = [
            'select' => [
                sprintf('o.%s AS id', $targetIdField),
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

        $entityId = $datagrid->getParameters()->get('id', false);

        if ($entityId) {
            $datasource->bindParameters(['relation' => 'id']);
        }
    }
}
