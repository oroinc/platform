<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class DynamicFieldsExtension extends AbstractFieldsExtension
{
    const EXTEND_ENTITY_CONFIG_PATH = 'extended_entity_name';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetOr(self::EXTEND_ENTITY_CONFIG_PATH, false) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 300;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName(DatagridConfiguration $config)
    {
        return $config->offsetGetOr(self::EXTEND_ENTITY_CONFIG_PATH);
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
        $viewConfigProvider     = $this->configManager->getProvider('view');
        $datagridConfigProvider = $this->configManager->getProvider('datagrid');

        $fields = [];
        $fieldIds = $entityConfigProvider->getIds($entityClassName);
        /** @var FieldConfigId $fieldId */
        foreach ($fieldIds as $fieldId) {
            $extendConfig = $extendConfigProvider->getConfigById($fieldId);
            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                && ExtendHelper::isFieldAccessible($extendConfig)
                && $datagridConfigProvider->getConfigById($fieldId)->is('is_visible')
            ) {
                $viewConfig = $viewConfigProvider->getConfig($entityClassName, $fieldId->getFieldName());
                $fields[] = [
                    'id'       => $fieldId,
                    'priority' => $viewConfig->get('priority', false, 0)
                ];
            }
        }

        ArrayUtil::sortBy($fields, true);

        return array_map(
            function ($field) {
                return $field['id'];
            },
            $fields
        );
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
