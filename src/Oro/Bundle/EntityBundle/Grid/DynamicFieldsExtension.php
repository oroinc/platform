<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class DynamicFieldsExtension extends AbstractFieldsExtension
{
    const EXTEND_ENTITY_CONFIG_PATH = '[extended_entity_name]';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetByPath(self::EXTEND_ENTITY_CONFIG_PATH, false) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::EXTEND_ENTITY_CONFIG_PATH);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(DatagridConfiguration $config)
    {
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        if (!$this->configManager->hasConfig($entityClassName)) {
            return [];
        }

        $entityConfigProvider   = $this->configManager->getProvider('entity');
        $extendConfigProvider   = $this->configManager->getProvider('extend');
        $datagridConfigProvider = $this->configManager->getProvider('datagrid');

        $fields   = [];
        $fieldIds = $entityConfigProvider->getIds($entityClassName);
        foreach ($fieldIds as $fieldId) {
            $extendConfig = $extendConfigProvider->getConfigById($fieldId);
            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                && $datagridConfigProvider->getConfigById($fieldId)->is('is_visible')
                && !$extendConfig->is('state', ExtendScope::STATE_NEW)
                && !$extendConfig->is('is_deleted')
            ) {
                $fields[] = $fieldId;
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareColumnOptions(FieldConfigId $field, array &$columnOptions)
    {
        parent::prepareColumnOptions($field, $columnOptions);

        if ($this->getFieldConfig('datagrid', $field)->is('show_filter')) {
            $columnOptions[DatagridGuesser::FILTER]['enabled'] = true;
        }
    }
}
