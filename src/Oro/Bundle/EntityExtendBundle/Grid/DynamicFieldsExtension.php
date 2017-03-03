<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class DynamicFieldsExtension extends AbstractFieldsExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!parent::isApplicable($config) || !$config->getExtendedEntityClassName()) {
            return false;
        }

        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        /** @var ConfigProvider $extendProvider */
        $extendProvider = $this->configManager->getProvider('extend');
        if (!$extendProvider->hasConfig($entityClassName)) {
            return false;
        }

        $extendConfig = $extendProvider->getConfig($entityClassName);

        return $extendConfig->is('is_extend');
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
        return $config->getExtendedEntityClassName();
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
