<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Additional datagrid extension which adds to datagrids columns, filters and sorters for the extended entities fields.
 */
class AdditionalFieldsExtension extends AbstractFieldsExtension
{
    public const ADDITIONAL_FIELDS_CONFIG_PATH = '[options][additional_fields]';

    private const ENTITY_NAME_CONFIG_PATH = '[options][entity_name]';

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetByPath(self::ENTITY_NAME_CONFIG_PATH, false) !== false
            && \count($config->offsetGetByPath(self::ADDITIONAL_FIELDS_CONFIG_PATH, [])) > 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityName(DatagridConfiguration $config): string
    {
        return $config->offsetGetByPath(self::ENTITY_NAME_CONFIG_PATH);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFields(DatagridConfiguration $config): array
    {
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        if (!$this->configManager->hasConfig($entityClassName)) {
            return [];
        }

        $fieldNames = $config->offsetGetByPath(self::ADDITIONAL_FIELDS_CONFIG_PATH, []);

        $fields = [];
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
     * {@inheritDoc}
     */
    protected function prepareColumnOptions(FieldConfigId $field, array &$columnOptions): void
    {
        parent::prepareColumnOptions($field, $columnOptions);

        $columnOptions[DatagridGuesser::FILTER]['renderable'] = true;
    }
}
