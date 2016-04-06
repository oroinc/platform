<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class IndexEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param ConfigManager   $configManager
     * @param FieldTypeHelper $fieldTypeHelper
     */
    public function __construct(ConfigManager $configManager, FieldTypeHelper $fieldTypeHelper)
    {
        $this->configManager   = $configManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $targetEntityConfigs = $this->configManager->getProvider('extend')->getConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            if ($targetEntityConfig->is('is_extend')) {
                $indices = $targetEntityConfig->has('index')
                    ? $targetEntityConfig->get('index')
                    : [];
                if ($this->updateIndices($indices, $targetEntityConfig->getId()->getClassName())) {
                    if (empty($indices)) {
                        $targetEntityConfig->remove('index');
                    } else {
                        $targetEntityConfig->set('index', $indices);
                    }
                    $this->configManager->persist($targetEntityConfig);
                }
            }
        }
    }

    /**
     * @param array  $indices
     * @param string $targetEntityClass
     *
     * @return bool
     */
    protected function updateIndices(array &$indices, $targetEntityClass)
    {
        $hasChanges   = false;
        $fieldConfigs = $this->configManager->getProvider('extend')->getConfigs($targetEntityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->is('is_extend')) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldName = $fieldConfigId->getFieldName();
                $fieldType = $fieldConfigId->getFieldType();
                if ($this->isIndexRequired($fieldConfigId->getClassName(), $fieldName, $fieldType)) {
                    if (!isset($indices[$fieldName]) || !$indices[$fieldName]) {
                        // TODO: need to be changed to fieldName => columnName
                        // TODO: should be done in scope https://magecore.atlassian.net/browse/BAP-3940
                        $indices[$fieldName] = true;
                        $hasChanges          = true;
                    }
                } elseif (isset($indices[$fieldName]) || array_key_exists($fieldName, $indices)) {
                    unset($indices[$fieldName]);
                    $hasChanges = true;
                }
            }
        }

        return $hasChanges;
    }

    /**
     * Determines whether the index for the given field is needed or not.
     * All relation type fields should be excluded.
     * Index requirement is determined by visibility of a field on a grid.
     *
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return bool
     */
    protected function isIndexRequired($className, $fieldName, $fieldType)
    {
        $underlyingType = $this->fieldTypeHelper->getUnderlyingType($fieldType);
        if (!$this->fieldTypeHelper->isRelation($underlyingType)) {
            $datagridConfigProvider = $this->configManager->getProvider('datagrid');
            if ($datagridConfigProvider->hasConfig($className, $fieldName)) {
                $datagridConfig = $datagridConfigProvider->getConfig($className, $fieldName);

                return $datagridConfig->get('is_visible', false, DatagridScope::IS_VISIBLE_FALSE);
            }
        }

        return false;
    }
}
