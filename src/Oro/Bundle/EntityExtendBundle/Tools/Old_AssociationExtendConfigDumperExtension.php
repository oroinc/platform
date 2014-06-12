<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

abstract class Old_AssociationExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string[] */
    private $targetEntityClassNames;

    /** @var array|ConfigInterface[] */
    private $targetEntityConfigs;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Gets the entity class who is owning side of the association
     *
     * @throws \Exception
     * @return string
     */
    abstract protected function getAssociationEntityClass();

    /**
     * Gets the scope name where the association is declared
     *
     * @return string
     */
    abstract protected function getAssociationScope();

    /**
     * Check if entity target matched for further processing
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    abstract protected function targetEntityMatch(ConfigInterface $config);

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntities = $this->getTargetEntities();

            return !empty($targetEntities);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $entityClass          = $this->getAssociationEntityClass();
        $targetEntities       = $this->getTargetEntities();

        foreach ($targetEntities as $targetEntityClass) {
            $this->createRelation($entityClass, $targetEntityClass, 'manyToOne');
        }
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $relationType      manyToOne, manyToMany, etc
     */
    protected function createRelation($sourceEntityClass, $targetEntityClass, $relationType)
    {
        $relationName = $this->getRelationName($targetEntityClass);
        $relationKey  = $this->getRelationKey($sourceEntityClass, $relationName, $targetEntityClass);

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
            $relationType,
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

    /**
     * Gets the list of class names for entities that can be the target of the association
     *
     * @return string[] the list of class names
     */
    protected function getTargetEntities()
    {
        if (null === $this->targetEntityClassNames) {
            $configs = $this->getTargetEntitiesConfigs();
            foreach ($configs as $config) {
                $this->targetEntityClassNames[] = $config->getId()->getClassName();
            }
        }

        return $this->targetEntityClassNames;
    }

    /**
     * @return array|ConfigInterface[]
     */
    protected function getTargetEntitiesConfigs()
    {
        if (null === $this->targetEntityConfigs) {
            $this->targetEntityConfigs = [];

            $configs = $this->configManager->getProvider($this->getAssociationScope())->getConfigs();
            foreach ($configs as $config) {
                if ($this->targetEntityMatch($config)) {
                    $this->targetEntityConfigs[] = $config;
                }
            }
        }

        return $this->targetEntityConfigs;
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $targetEntityClassName
     *
     * @return string e.g. "manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|user"
     */
    protected function getRelationKey($entityClassName, $fieldName, $targetEntityClassName)
    {
        return ExtendHelper::buildRelationKey($entityClassName, $fieldName, 'manyToOne', $targetEntityClassName);
    }

    /**
     * @param string $targetEntityClassName
     *
     * @return string
     */
    protected function getRelationName($targetEntityClassName)
    {
        return ExtendHelper::buildAssociationName($targetEntityClassName);
    }

    /**
     * @param string $entityClassName
     *
     * @return string[]
     */
    protected function getPrimaryKeyColumnNames($entityClassName)
    {
        try {
            $targetEntityMetadata = $this->configManager->getEntityManager()
                ->getClassMetadata($entityClassName);

            return $targetEntityMetadata->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            return ['id'];
        }
    }
}
