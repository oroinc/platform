<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\EntityConfig\IndexScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * Entity dumper extension to created indexes if a field is visible in datagrid
 */
class IndexEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    public function __construct(ConfigManager $configManager, FieldTypeHelper $fieldTypeHelper)
    {
        $this->configManager = $configManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

    #[\Override]
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function preUpdate()
    {
        $targetEntityConfigs = $this->configManager->getProvider('extend')->getConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            if ($targetEntityConfig->is('is_extend')) {
                $indexes = $targetEntityConfig->has('index')
                    ? $targetEntityConfig->get('index')
                    : [];
                if ($this->updateIndexes($indexes, $targetEntityConfig->getId()->getClassName())) {
                    if (empty($indexes)) {
                        $targetEntityConfig->remove('index');
                    } else {
                        $targetEntityConfig->set('index', $indexes);
                    }
                    $this->configManager->persist($targetEntityConfig);
                }
            }
        }
    }

    /**
     * @param array $indexes
     * @param string $targetEntityClass
     *
     * @return bool
     */
    protected function updateIndexes(array &$indexes, $targetEntityClass)
    {
        $hasChanges = false;
        $fieldConfigs = $this->configManager->getProvider('extend')->getConfigs($targetEntityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->is('is_extend')) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldName = $fieldConfigId->getFieldName();
                $fieldType = $fieldConfigId->getFieldType();
                if ($this->isIndexRequired($fieldConfig, $fieldConfigId->getClassName(), $fieldName, $fieldType)) {
                    if (!isset($indexes[$fieldName]) || !$indexes[$fieldName]) {
                        // see BAP-3940
                        $indexes[$fieldName] = IndexScope::INDEX_SIMPLE;
                        if ($fieldConfig->is('unique')) {
                            $indexes[$fieldName] = IndexScope::INDEX_UNIQUE;
                        }
                        $hasChanges = true;
                    }
                } elseif (isset($indexes[$fieldName]) || array_key_exists($fieldName, $indexes)) {
                    unset($indexes[$fieldName]);
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
     */
    protected function isIndexRequired(
        ConfigInterface $fieldConfig,
        string $className,
        string $fieldName,
        string $fieldType
    ): bool {
        $underlyingType = $this->fieldTypeHelper->getUnderlyingType($fieldType, $fieldConfig);
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
