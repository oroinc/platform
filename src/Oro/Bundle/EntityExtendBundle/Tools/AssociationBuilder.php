<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationBuilder
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
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     */
    public function createManyToManyAssociation($sourceEntityClass, $targetEntityClass)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass);
        $relationKey  = ExtendHelper::buildRelationKey(
            $sourceEntityClass,
            $relationName,
            'manyToMany',
            $targetEntityClass
        );

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);

        // create field
        $this->addFieldConfig(
            $sourceEntityClass,
            $relationName,
            'manyToMany',
            [
                'extend' => [
                    'owner'           => ExtendScope::OWNER_SYSTEM,
                    'state'           => ExtendScope::STATE_NEW,
                    'extend'          => true,
                    'is_inverse'      => false,
                    'relation_key'    => $relationKey,
                    'target_entity'   => $targetEntityClass,
                    'target_grid'     => $targetEntityPrimaryKeyColumns,
                    'target_title'    => $targetEntityPrimaryKeyColumns,
                    'target_detailed' => $targetEntityPrimaryKeyColumns,
                ],
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => true
                ],
                'form'   => [
                    'is_enabled' => true
                ]
            ]
        );

        // add relation to owning entity
        $this->addManyToManyRelation(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     */
    public function createManyToOneAssociation($sourceEntityClass, $targetEntityClass)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass);
        $relationKey  = ExtendHelper::buildRelationKey(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            $targetEntityClass
        );

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);
        $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);

        // create field
        $this->addFieldConfig(
            $sourceEntityClass,
            $relationName,
            'manyToOne',
            [
                'extend' => [
                    'owner'         => ExtendScope::OWNER_SYSTEM,
                    'state'         => ExtendScope::STATE_NEW,
                    'extend'        => true,
                    'target_entity' => $targetEntityClass,
                    'target_field'  => $targetFieldName,
                    'relation_key'  => $relationKey,
                ],
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => false
                ],
                'form'   => [
                    'is_enabled' => false
                ]
            ]
        );

        // add relation to owning entity
        $this->addManyToOneRelation(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );

        // add relation to target entity
        $this->addManyToOneRelationTargetSide(
            $targetEntityClass,
            $sourceEntityClass,
            $relationName,
            $relationKey
        );
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    protected function getPrimaryKeyColumnNames($entityClass)
    {
        try {
            return $this->configManager
                ->getEntityManager()
                ->getClassMetadata($entityClass)
                ->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            return ['id'];
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     */
    protected function addFieldConfig($className, $fieldName, $fieldType, $values)
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
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
     */
    protected function addManyToManyRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey
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
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     */
    protected function addManyToOneRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey
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
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     */
    protected function addManyToOneRelationTargetSide(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey
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
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }
}
