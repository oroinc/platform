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

                // @todo: we need to find a way to use this extension to process OWNER_SYSTEM relations as well
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
            $this->createSelfRelation($fieldConfig);
            $this->ensureReverseRelationCompleted($fieldConfig);
        } else {
            $entityConfig = $this->getEntityConfig($fieldConfig->getId()->getClassName());
            $relations = $entityConfig->get('relation', false, []);
            $relationKey = $fieldConfig->get('relation_key');
            if (!isset($relations[$relationKey])) {
                $this->createSelfRelation($fieldConfig);
                $this->ensureReverseRelationCompleted($fieldConfig);
            } else {
                $relation = $relations[$relationKey];
                if (array_key_exists('owner', $relation)) {
                    $this->ensureReverseRelationCompleted($fieldConfig);
                } elseif (isset($relation['target_field_id']) && $relation['target_field_id']) {
                    // this completion is required when a bidirectional relation is created from a migration
                    $this->completeSelfRelation($fieldConfig);
                }
            }
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function createSelfRelation(ConfigInterface $fieldConfig)
    {
        $selfFieldId = $this->createFieldConfigId($fieldConfig);
        $targetFieldId = false;

        $selfIsOwnerSide = true;

        $selfRelationKey = $fieldConfig->get('relation_key');
        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);

        $selfEntityClass = $selfFieldId->getClassName();
        $selfConfig = $this->getEntityConfig($selfEntityClass);
        $selfRelations = $selfConfig->get('relation', false, []);

        $targetEntityClass = $fieldConfig->get('target_entity');
        $targetConfig = null;
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

        if (!$targetFieldId && in_array($selfFieldId->getFieldType(), RelationType::$toManyRelations, true)) {
            $targetFieldId = new FieldConfigId(
                'extend',
                $targetEntityClass,
                ExtendHelper::buildToManyRelationTargetFieldName(
                    $selfEntityClass,
                    $selfFieldId->getFieldName()
                ),
                ExtendHelper::getReverseRelationType($selfFieldId->getFieldType())
            );
        }
        if ($selfFieldId->getFieldType() === RelationType::ONE_TO_MANY) {
            $selfIsOwnerSide = false;
        }

        $selfRelation = [
            'field_id'        => $selfFieldId,
            'owner'           => $selfIsOwnerSide,
            'target_entity'   => $targetEntityClass,
            'target_field_id' => $targetFieldId
        ];
        if ($fieldConfig->has('cascade')) {
            $selfRelation['cascade'] = $fieldConfig->get('cascade');
        }
        if ($fieldConfig->has('on_delete')) {
            $selfRelation['on_delete'] = $fieldConfig->get('on_delete');
        }
        if ($fieldConfig->has('nullable')) {
            $selfRelation['nullable'] = $fieldConfig->get('nullable');
        }

        $targetRelation = [
            'field_id'        => $targetFieldId,
            'owner'           => !$selfIsOwnerSide,
            'target_entity'   => $selfEntityClass,
            'target_field_id' => $selfFieldId,
        ];

        $selfRelations[$selfRelationKey] = $selfRelation;
        if (null === $targetRelations) {
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
     * Makes sure that both source and target entities know about a reverse relation
     *
     * @param ConfigInterface $fieldConfig
     */
    protected function ensureReverseRelationCompleted(ConfigInterface $fieldConfig)
    {
        $selfRelationKey = $fieldConfig->get('relation_key');

        $selfClassName = $fieldConfig->getId()->getClassName();
        $selfConfig = $this->getEntityConfig($selfClassName);
        $selfRelations = $selfConfig->get('relation', false, []);
        if (isset($selfRelations[$selfRelationKey]['field_id']) && $selfRelations[$selfRelationKey]['field_id']) {
            return;
        }

        $targetRelationKey = ExtendHelper::toggleRelationKey($selfRelationKey);
        $targetClassName = $fieldConfig->get('target_entity');
        if ($targetClassName === $selfClassName) {
            if (isset($selfRelations[$targetRelationKey])) {
                $selfFieldId = $this->createFieldConfigId($fieldConfig);
                $selfRelations[$selfRelationKey]['field_id'] = $selfFieldId;
                $selfRelations[$targetRelationKey]['target_field_id'] = $selfFieldId;
                $selfConfig->set('relation', $selfRelations);
                $this->configManager->persist($selfConfig);
            }
        } else {
            $targetConfig = $this->getEntityConfig($targetClassName);
            $targetRelations = $targetConfig->get('relation', false, []);
            if (isset($targetRelations[$targetRelationKey])) {
                $selfFieldId = $this->createFieldConfigId($fieldConfig);
                $selfRelations[$selfRelationKey]['field_id'] = $selfFieldId;
                $targetRelations[$targetRelationKey]['target_field_id'] = $selfFieldId;
                $selfConfig->set('relation', $selfRelations);
                $targetConfig->set('relation', $targetRelations);
                $this->configManager->persist($selfConfig);
                $this->configManager->persist($targetConfig);
            }
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    protected function completeSelfRelation(ConfigInterface $fieldConfig)
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
        if ($fieldConfig->has('cascade')) {
            $selfRelation['cascade'] = $fieldConfig->get('cascade');
        }
        $selfRelations[$selfRelationKey] = $selfRelation;

        if ($targetEntityClass === $selfEntityClass) {
            $targetRelation = $selfRelations[$targetRelationKey];
            $targetRelation['owner'] = !$selfIsOwnerSide;
            $targetRelation['target_entity'] = $selfEntityClass;
            $targetRelation['target_field_id'] = $selfFieldId;
            $selfRelations[$targetRelationKey] = $targetRelation;
        } else {
            $targetConfig = $this->getEntityConfig($targetEntityClass);
            $targetRelations = $targetConfig->get('relation', false, []);

            $targetRelation = $targetRelations[$targetRelationKey];
            $targetRelation['owner'] = !$selfIsOwnerSide;
            $targetRelation['target_entity'] = $selfEntityClass;
            $targetRelation['target_field_id'] = $selfFieldId;
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
    protected function setRelationKeyToFieldConfig(ConfigInterface $fieldConfig)
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
     * @param string $className
     *
     * @return ConfigInterface
     */
    protected function getEntityConfig($className)
    {
        return $this->configManager->getEntityConfig('extend', $className);
    }

    /**
     * @param ConfigInterface $fieldConfig
     *
     * @return FieldConfigId
     */
    protected function createFieldConfigId(ConfigInterface $fieldConfig)
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
}
