<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class RelationBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     */
    public function addFieldConfig($className, $fieldName, $fieldType, $values)
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
        $this->updateFieldConfigs($className, $fieldName, $values);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $values
     */
    public function updateFieldConfigs($className, $fieldName, $values)
    {
        foreach ($values as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $fieldConfig    = $configProvider->getConfig($className, $fieldName);
            foreach ($scopeValues as $code => $val) {
                $fieldConfig->set($code, $val);
            }
            $configProvider->persist($fieldConfig);
            $this->configManager->calculateConfigChangeSet($fieldConfig);
        }
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param array  $relationOptions
     */
    public function addManyToManyRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $relationOptions = []
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($sourceEntityName);

        // update index info
        $index                = $extendConfig->get('index', false, []);
        $index[$relationName] = null;
        $extendConfig->set('index', $index);

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToMany'),
            'owner'           => true,
            'target_entity'   => $targetEntityName,
            'target_field_id' => false,
        ];
        if (!empty($relationOptions)) {
            $relations[$relationKey] = array_merge($relations[$relationKey], $relationOptions);
        }
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param array  $relationOptions
     */
    public function addManyToOneRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $relationOptions = []
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($sourceEntityName);

        // update schema info
        $schema                            = $extendConfig->get('schema', false, []);
        $schema['relation'][$relationName] = $relationName;
        $extendConfig->set('schema', $schema);

        // update index info
        $index                = $extendConfig->get('index', false, []);
        $index[$relationName] = null;
        $extendConfig->set('index', $index);

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne'),
            'owner'           => true,
            'target_entity'   => $targetEntityName,
            'target_field_id' => false
        ];
        if (!empty($relationOptions)) {
            $relations[$relationKey] = array_merge($relations[$relationKey], $relationOptions);
        }
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param array  $relationOptions
     */
    public function addManyToOneRelationTargetSide(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $relationOptions = []
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($targetEntityName);

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => false,
            'owner'           => false,
            'target_entity'   => $sourceEntityName,
            'target_field_id' => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne')
        ];
        if (!empty($relationOptions)) {
            $relations[$relationKey] = array_merge($relations[$relationKey], $relationOptions);
        }
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }
}
