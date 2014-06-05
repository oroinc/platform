<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

abstract class AssociationExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string[] */
    private $targetEntityClassNames;

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
     * Gets the config attribute name which indicates whether the association is enabled or not
     *
     * @return string
     */
    protected function getAssociationAttributeName()
    {
        return 'enabled';
    }

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
        $entityClassName = $this->getAssociationEntityClass();
        $targetEntities  = $this->getTargetEntities();
        foreach ($targetEntities as $targetEntityClassName) {
            $relationName = $this->getRelationName($targetEntityClassName);
            $relationKey  = $this->getRelationKey($entityClassName, $relationName, $targetEntityClassName);

            $entityConfigProvider = $this->configManager->getProvider('entity');
            $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClassName);

            $label       = $targetEntityConfig->get(
                'label',
                false,
                ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClassName, $relationName)
            );
            $description = ConfigHelper::getTranslationKey(
                'entity',
                'description',
                $targetEntityClassName,
                $relationName
            );

            $targetEntityMetadata          = $this->configManager->getEntityManager()
                ->getClassMetadata($targetEntityClassName);
            $targetEntityPrimaryKeyColumns = $targetEntityMetadata->getIdentifierColumnNames();
            $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);

            // create field
            $this->createField(
                $entityClassName,
                $relationName,
                'manyToOne',
                [
                    'extend'    => [
                        'owner'         => ExtendScope::OWNER_SYSTEM,
                        'state'         => ExtendScope::STATE_NEW,
                        'is_extend'     => false,
                        'extend'        => true,
                        'is_deleted'    => false,
                        'is_inverse'    => false,
                        'target_entity' => $targetEntityClassName,
                        'target_field'  => $targetFieldName,
                        'relation_key'  => $relationKey,
                    ],
                    'entity'    => [
                        'label'       => $label,
                        'description' => $description,
                    ],
                    'view'      => [
                        'is_displayable' => false
                    ],
                    'form'      => [
                        'is_enabled' => true
                    ],
                    'dataaudit' => [
                        'auditable' => false
                    ]
                ]
            );

            // add relation to owning entity
            $this->addManyToOneRelation(
                $targetEntityClassName,
                $entityClassName,
                $relationName,
                $relationKey
            );

            // add relation to target entity
            $this->addManyToOneRelationTargetSide(
                $targetEntityClassName,
                $entityClassName,
                $relationName,
                $relationKey
            );
        }
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
     * Gets the list of class names for entities which can the target of the association
     *
     * @return string[] the list of class names
     */
    protected function getTargetEntities()
    {
        if (null === $this->targetEntityClassNames) {
            $this->targetEntityClassNames = [];

            $configs = $this->configManager->getProvider($this->getAssociationScope())->getConfigs();
            foreach ($configs as $config) {
                if ($config->is($this->getAssociationAttributeName())) {
                    $this->targetEntityClassNames[] = $config->getId()->getClassName();
                }
            }
        }

        return $this->targetEntityClassNames;
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
}
