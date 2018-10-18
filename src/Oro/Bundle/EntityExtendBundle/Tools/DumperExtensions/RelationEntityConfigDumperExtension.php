<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Handles saving relation configuration of entities.
 */
class RelationEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
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
        $this->configManager = $configManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        return ExtendConfigDumper::ACTION_PRE_UPDATE === $actionType;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $entityConfigs = $this->configManager->getConfigs('extend');
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $fieldConfigs = $this->configManager->getConfigs('extend', $entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATE])) {
                    continue;
                }

                // we need to find a way to use this extension to process OWNER_SYSTEM relations as well
                // currently we have several problems here:
                // - collision with associations
                // - no support unidirectional relations
                if (!$fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                    continue;
                }

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if (in_array($fieldConfigId->getFieldType(), RelationType::$anyToAnyRelations, true)) {
                    $this->createRelation($fieldConfig);
                }
            }
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    protected function createRelation(ConfigInterface $fieldConfig)
    {
        if (!$fieldConfig->is('relation_key')) {
            $this->setRelationKeyToFieldConfig($fieldConfig);
        }

        $entityConfig = $this->getEntityConfig($fieldConfig->getId()->getClassName());
        $relations = $entityConfig->get('relation', false, []);
        $relationKey = $fieldConfig->get('relation_key');

        if (!isset($relations[$relationKey])) {
            $this->createOwningSideOfRelation($fieldConfig);
            if ($fieldConfig->get('bidirectional')) {
                $this->createInverseSideOfRelation($fieldConfig);
            }
        } else {
            // this completion is required when a bidirectional relation is created from a migration
            $relation = $relations[$relationKey];
            if (!array_key_exists('owner', $relation)
                && isset($relation['target_field_id'])
                && $relation['target_field_id']
            ) {
                $this->completeRelation($fieldConfig);
            }
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    private function createOwningSideOfRelation(ConfigInterface $fieldConfig)
    {
        $selfFieldId = $this->createFieldConfigId($fieldConfig);
        $selfConfig = $this->getEntityConfig($selfFieldId->getClassName());
        $selfRelations = $selfConfig->get('relation', false, []);

        $selfRelation = [
            'field_id' => $selfFieldId,
            'owner' => $selfFieldId->getFieldType() !== RelationType::ONE_TO_MANY,
            'target_entity' => $fieldConfig->get('target_entity'),
            'target_field_id' => false
        ];
        $this->setRelationOptions($selfRelation, $fieldConfig);

        $selfRelations[$fieldConfig->get('relation_key')] = $selfRelation;

        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    private function createInverseSideOfRelation(ConfigInterface $fieldConfig)
    {
        $selfFieldId = $this->createFieldConfigId($fieldConfig);
        $selfConfig = $this->getEntityConfig($selfFieldId->getClassName());
        $selfEntityClass = $selfFieldId->getClassName();
        $selfRelations = $selfConfig->get('relation', false, []);
        $selfRelation = $selfRelations[$fieldConfig->get('relation_key')];

        $selfRelationKey = $fieldConfig->get('relation_key');
        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);

        $targetFieldId = false;
        $targetEntityClass = $fieldConfig->get('target_entity');
        $targetConfig = null;
        $targetFieldConfig = null;
        $targetRelations = null;

        if ($targetEntityClass === $selfEntityClass) {
            if (isset($selfRelations[$targetRelationKey]['field_id'])) {
                $targetFieldId = $selfRelations[$targetRelationKey]['field_id'];
            }
        } else {
            $targetConfig = $this->getEntityConfig($targetEntityClass);
            $targetRelations = $targetConfig->get('relation', false, []);
            if (isset($targetRelations[$targetRelationKey]['field_id'])) {
                $targetFieldId = $targetRelations[$targetRelationKey]['field_id'];
            }
        }

        if (!$targetFieldId) {
            $targetFieldId = $this->createTargetFieldConfigId(
                $selfEntityClass,
                $selfFieldId->getFieldName(),
                $selfFieldId->getFieldType(),
                $targetEntityClass
            );
        }
        $targetFieldConfig = $this->findFieldConfigById($targetFieldId);

        $selfRelation['target_field_id'] = $targetFieldId;
        $selfRelations[$selfRelationKey] = $selfRelation;

        // inverse side of relation
        $targetRelation = [
            'field_id' => $targetFieldId,
            'owner' => !$selfRelation['owner'],
            'target_entity' => $selfEntityClass,
            'target_field_id' => $selfFieldId,
        ];
        if (null !== $targetFieldConfig) {
            $this->setRelationOptions($targetRelation, $targetFieldConfig);
        }

        if ($targetEntityClass === $selfEntityClass) {
            $selfRelations[$targetRelationKey] = $targetRelation;
        } else {
            $targetRelations[$targetRelationKey] = $targetRelation;
        }

        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);
        if (null !== $targetConfig) {
            $targetConfig->set('relation', $targetRelations);
            $this->configManager->persist($targetConfig);
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    private function completeRelation(ConfigInterface $fieldConfig)
    {
        $selfRelationKey = $fieldConfig->get('relation_key');

        $selfEntityClass = $fieldConfig->getId()->getClassName();
        $selfFieldId = $this->createFieldConfigId($fieldConfig);
        $selfConfig = $this->getEntityConfig($selfEntityClass);
        $selfRelations = $selfConfig->get('relation', false, []);
        $selfRelation = $selfRelations[$selfRelationKey];

        $selfIsOwnerSide = true;
        if ($selfFieldId->getFieldType() === RelationType::ONE_TO_MANY) {
            $selfIsOwnerSide = false;
        }

        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);
        $targetEntityClass = $fieldConfig->get('target_entity');

        $selfRelation['field_id'] = $selfFieldId;
        $selfRelation['owner'] = $selfIsOwnerSide;
        $selfRelation['target_entity'] = $targetEntityClass;
        $this->setRelationOptions($selfRelation, $fieldConfig);
        $selfRelations[$selfRelationKey] = $selfRelation;

        if ($targetEntityClass === $selfEntityClass) {
            $targetRelation = $selfRelations[$targetRelationKey];
            $targetRelation['owner'] = !$selfIsOwnerSide;
            $targetRelation['target_entity'] = $selfEntityClass;
            $targetRelation['target_field_id'] = $selfFieldId;
            /** @var FieldConfigId $relationFieldId */
            $relationFieldId = $targetRelation['field_id'];
            $relationFieldConfig = $this->findFieldConfigById($relationFieldId);
            if (null !== $relationFieldConfig) {
                $this->setRelationOptions($targetRelation, $relationFieldConfig);
            }
            $selfRelations[$targetRelationKey] = $targetRelation;
        } else {
            $targetConfig = $this->getEntityConfig($targetEntityClass);
            $targetRelations = $targetConfig->get('relation', false, []);

            $targetRelation = $targetRelations[$targetRelationKey];
            $targetRelation['owner'] = !$selfIsOwnerSide;
            $targetRelation['target_entity'] = $selfEntityClass;
            $targetRelation['target_field_id'] = $selfFieldId;
            /** @var FieldConfigId $relationFieldId */
            $relationFieldId = $targetRelation['field_id'];
            $relationFieldConfig = $this->findFieldConfigById($relationFieldId);
            if (null !== $relationFieldConfig) {
                $this->setRelationOptions($targetRelation, $relationFieldConfig);
            }
            $targetRelations[$targetRelationKey] = $targetRelation;

            $targetConfig->set('relation', $targetRelations);
            $this->configManager->persist($targetConfig);
        }

        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    private function setRelationKeyToFieldConfig(ConfigInterface $fieldConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $fieldConfig->getId();

        $relationKey = ExtendHelper::buildRelationKey(
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType()),
            $fieldConfig->get('target_entity')
        );

        $fieldConfig->set('relation_key', $relationKey);
        $this->configManager->persist($fieldConfig);
    }

    /**
     * @param array           $relation
     * @param ConfigInterface $fieldConfig
     */
    private function setRelationOptions(array &$relation, ConfigInterface $fieldConfig)
    {
        if ($fieldConfig->has('cascade')) {
            $relation['cascade'] = $fieldConfig->get('cascade');
        }
        if ($fieldConfig->has('fetch')) {
            $relation['fetch'] = $fieldConfig->get('fetch');
        }
        if ($fieldConfig->has('on_delete')) {
            $relation['on_delete'] = $fieldConfig->get('on_delete');
        }
        if ($fieldConfig->has('nullable')) {
            $relation['nullable'] = $fieldConfig->get('nullable');
        }
    }

    /**
     * @param string $className
     *
     * @return ConfigInterface
     */
    private function getEntityConfig($className)
    {
        return $this->configManager->getEntityConfig('extend', $className);
    }

    /**
     * @param FieldConfigId $fieldConfigId
     *
     * @return ConfigInterface|null
     */
    private function findFieldConfigById(FieldConfigId $fieldConfigId)
    {
        $className = $fieldConfigId->getClassName();
        $fieldName = $fieldConfigId->getFieldName();
        if (!$this->configManager->hasConfig($className, $fieldName)) {
            return null;
        }

        return $this->configManager->getFieldConfig('extend', $className, $fieldName);
    }

    /**
     * @param ConfigInterface $fieldConfig
     *
     * @return FieldConfigId
     */
    private function createFieldConfigId(ConfigInterface $fieldConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $fieldConfig->getId();

        return new FieldConfigId(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType())
        );
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param string $fieldType
     * @param string $targetEntityClass
     *
     * @return FieldConfigId
     */
    private function createTargetFieldConfigId($entityClass, $fieldName, $fieldType, $targetEntityClass)
    {
        return new FieldConfigId(
            'extend',
            $targetEntityClass,
            ExtendHelper::buildToManyRelationTargetFieldName($entityClass, $fieldName),
            ExtendHelper::getReverseRelationType($fieldType)
        );
    }
}
