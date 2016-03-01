<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AdditionalFieldsExtension extends AbstractFieldsExtension
{
    const ENTITY_NAME_CONFIG_PATH       = '[options][entity_name]';
    const ADDITIONAL_FIELDS_CONFIG_PATH = '[options][additional_fields]';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetByPath(self::ENTITY_NAME_CONFIG_PATH, false) !== false
            && count($config->offsetGetByPath(self::ADDITIONAL_FIELDS_CONFIG_PATH, [])) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::ENTITY_NAME_CONFIG_PATH);
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

        $fieldNames = $config->offsetGetByPath(self::ADDITIONAL_FIELDS_CONFIG_PATH, []);

        $fields               = [];
        $extendConfigProvider = $this->configManager->getProvider('extend');
        foreach ($fieldNames as $fieldName) {
            if (!$extendConfigProvider->hasConfig($entityClassName, $fieldName)) {
                continue;
            }
            $extendConfig = $extendConfigProvider->getConfig($entityClassName, $fieldName);
            if (ExtendHelper::isFieldAccessible($extendConfig)) {
                $fields[] = $extendConfig->getId();
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

        $columnOptions[DatagridGuesser::FILTER]['enabled'] = true;
    }
}
