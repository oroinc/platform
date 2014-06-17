<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationBuildHelper
{
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $scope
     *
     * @return array|ConfigInterface[]
     */
    public function getScopeConfigs($scope)
    {
        return $this->configManager->getProvider($scope)->getConfigs();
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param bool   $unidirectional
     */
    public function createManyToManyRelation($sourceEntityClass, $targetEntityClass, $unidirectional = true)
    {
        $relationName = $this->getRelationName($targetEntityClass);
        $relationKey = $this->getRelationKey(
            $sourceEntityClass,
            $relationName,
            $targetEntityClass,
            'manyToMany'
        );

        $targetEntityConfig = $this->configManager
            ->getProvider('entity')
            ->getConfig($targetEntityClass);
        $label              = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description        = ConfigHelper::getTranslationKey(
            'entity',
            'description',
            $targetEntityClass,
            $relationName
        );

        $targetEntityFields = $this->getFieldNames($targetEntityClass);
        $targetFieldName    = array_shift($targetEntityFields);

        // create field
        $this->createField(
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
                    'target_grid'     => $targetEntityFields,
                    'target_title'    => [$targetFieldName],
                    'target_detailed' => $targetEntityFields,
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
            $relationKey,
            $unidirectional
        );
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     */
    public function createManyToOneRelation($sourceEntityClass, $targetEntityClass)
    {
        $relationName = $this->getRelationName($targetEntityClass);
        $relationKey = $this->getRelationKey(
            $sourceEntityClass,
            $relationName,
            $targetEntityClass,
            'manyToOne'
        );

        $targetEntityConfig = $this->configManager
            ->getProvider('entity')
            ->getConfig($targetEntityClass);
        $label              = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description        = ConfigHelper::getTranslationKey(
            'entity',
            'description',
            $targetEntityClass,
            $relationName
        );

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);
        $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);

        // create field
        $this->createField(
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
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $targetEntityClass
     * @param string $relationType manyToOne, manyToMany, etc
     *
     * @return string e.g. "manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|user"
     */
    public function getRelationKey($entityClassName, $fieldName, $targetEntityClass, $relationType)
    {
        return ExtendHelper::buildRelationKey($entityClassName, $fieldName, $relationType, $targetEntityClass);
    }

    /**
     * @param string $targetEntityClass
     *
     * @return string
     */
    public function getRelationName($targetEntityClass)
    {
        return ExtendHelper::buildAssociationName($targetEntityClass);
    }

    /**
     * @param string $entityClass
     *
     * @return string[]|ClassMetadata
     */
    public function getPrimaryKeyColumnNames($entityClass)
    {
        try {
            $targetEntityMetadata = $this->configManager->getEntityManager()
                ->getClassMetadata($entityClass);

            return $targetEntityMetadata->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            return ['id'];
        }
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    public function getFieldNames($entityClass)
    {
        $targetEntityMetadata = $this->configManager->getEntityManager()
            ->getClassMetadata($entityClass);

        return $targetEntityMetadata->getFieldNames();
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $values
     */
    protected function updateFieldConfigs($className, $fieldName, array $values)
    {
        foreach ($values as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $fieldConfig    = $configProvider->getConfig($className, $fieldName);
            foreach ($scopeValues as $code => $val) {
                $fieldConfig->set($code, $val);
            }
            $this->configManager->persist($fieldConfig);
            $this->configManager->calculateConfigChangeSet($fieldConfig);
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     */
    protected function createField($className, $fieldName, $fieldType, $values)
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);

        $this->updateFieldConfigs(
            $className,
            $fieldName,
            $values
        );
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param bool   $unidirectional
     */
    protected function addManyToManyRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $unidirectional
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($sourceEntityName);

        // update index info
        $index                = $extendConfig->get('index', false, []);
        $index[$relationName] = null;
        $extendConfig->set('index', $index);

        $targetFieldId = null;
        if (!$unidirectional) {
            $targetFieldId = new FieldConfigId(
                'extend',
                $targetEntityName,
                $this->getRelationName($sourceEntityName) . '_' . $relationName,
                'manyToMany'
            );
        }

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToMany'),
            'owner'           => true,
            'target_entity'   => $targetEntityName,
            'target_field_id' => $targetFieldId,
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
