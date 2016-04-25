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
        } else {
            $entityConfig = $this->getEntityConfig($fieldConfig->getId()->getClassName());
            $relations    = $entityConfig->get('relation', false, []);
            $relationKey  = $fieldConfig->get('relation_key');
            if (!isset($relations[$relationKey])) {
                $this->createSelfRelation($fieldConfig);
                $this->ensureReverseRelationCompleted($fieldConfig);
            } else {
                $relation = $relations[$relationKey];
                if (array_key_exists('owner', $relation)) {
                    $this->ensureReverseRelationCompleted($fieldConfig);
                } elseif (isset($relation['target_field_id']) && $relation['target_field_id']) {
                    $this->completeSelfRelation($fieldConfig);
                }
            }
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    protected function createSelfRelation(ConfigInterface $fieldConfig)
    {
        $selfFieldId     = $this->createFieldConfigId($fieldConfig);
        $selfIsOwnerSide = true;

        $targetFieldId     = false;
        $targetEntityClass = $fieldConfig->get('target_entity');
        if (in_array($selfFieldId->getFieldType(), RelationType::$toManyRelations, true)) {
            $relationFieldName = ExtendHelper::buildToManyRelationTargetFieldName(
                $selfFieldId->getClassName(),
                $selfFieldId->getFieldName()
            );
            if ($selfFieldId->getFieldType() === RelationType::ONE_TO_MANY) {
                $selfIsOwnerSide = false;
            }

            $targetFieldId = new FieldConfigId(
                'extend',
                $targetEntityClass,
                $relationFieldName,
                ExtendHelper::getReverseRelationType($selfFieldId->getFieldType())
            );
        }

        $relationKey = $fieldConfig->get('relation_key');

        $selfConfig   = $this->getEntityConfig($selfFieldId->getClassName());
        $selfRelation = [
            'field_id'        => $selfFieldId,
            'owner'           => $selfIsOwnerSide,
            'target_entity'   => $targetEntityClass,
            'target_field_id' => $targetFieldId
        ];
        if ($fieldConfig->has('cascade')) {
            $selfRelation['cascade'] = $fieldConfig->get('cascade');
        }
        $selfRelations               = $selfConfig->get('relation', false, []);
        $selfRelations[$relationKey] = $selfRelation;
        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);

        $targetConfig                  = $this->getEntityConfig($targetEntityClass);
        $targetRelation                = [
            'field_id'        => $targetFieldId,
            'owner'           => !$selfIsOwnerSide,
            'target_entity'   => $selfFieldId->getClassName(),
            'target_field_id' => $selfFieldId,
        ];
        $targetRelations               = $targetConfig->get('relation', false, []);
        $targetRelations[$relationKey] = $targetRelation;
        $targetConfig->set('relation', $targetRelations);
        $this->configManager->persist($targetConfig);
    }

    /**
     * Makes sure that both source and target entities know about a reverse relation
     *
     * @param ConfigInterface $fieldConfig
     */
    protected function ensureReverseRelationCompleted(ConfigInterface $fieldConfig)
    {
        $relationKey = $fieldConfig->get('relation_key');

        $selfConfig    = $this->getEntityConfig($fieldConfig->getId()->getClassName());
        $selfRelations = $selfConfig->get('relation', false, []);
        if (isset($selfRelations[$relationKey]['field_id']) && $selfRelations[$relationKey]['field_id']) {
            return;
        }

        $targetConfig    = $this->getEntityConfig($fieldConfig->get('target_entity'));
        $targetRelations = $targetConfig->get('relation', false, []);
        if (!isset($targetRelations[$relationKey])) {
            return;
        }

        $selfFieldId = $this->createFieldConfigId($fieldConfig);

        $selfRelations[$relationKey]['field_id']          = $selfFieldId;
        $targetRelations[$relationKey]['target_field_id'] = $selfFieldId;

        $selfConfig->set('relation', $selfRelations);
        $targetConfig->set('relation', $targetRelations);

        $this->configManager->persist($selfConfig);
        $this->configManager->persist($targetConfig);
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    protected function completeSelfRelation(ConfigInterface $fieldConfig)
    {
        $relationKey = $fieldConfig->get('relation_key');

        $selfEntityClass = $fieldConfig->getId()->getClassName();
        $selfFieldId     = $this->createFieldConfigId($fieldConfig);
        $selfConfig      = $this->getEntityConfig($selfEntityClass);
        $selfRelations   = $selfConfig->get('relation', false, []);
        $selfRelation    = $selfRelations[$relationKey];

        $targetEntityClass = $fieldConfig->get('target_entity');
        $targetConfig      = $this->getEntityConfig($targetEntityClass);
        $targetRelations   = $targetConfig->get('relation', false, []);
        $targetRelation    = $targetRelations[$relationKey];

        $selfIsOwnerSide = true;
        if ($selfFieldId->getFieldType() === RelationType::ONE_TO_MANY) {
            $selfIsOwnerSide = false;
        }

        $selfRelation['field_id']      = $selfFieldId;
        $selfRelation['owner']         = $selfIsOwnerSide;
        $selfRelation['target_entity'] = $targetEntityClass;
        if ($fieldConfig->has('cascade')) {
            $selfRelation['cascade'] = $fieldConfig->get('cascade');
        }
        $selfRelations[$relationKey] = $selfRelation;
        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);

        $targetRelation['owner']           = !$selfIsOwnerSide;
        $targetRelation['target_entity']   = $selfEntityClass;
        $targetRelation['target_field_id'] = $selfFieldId;
        $targetRelations[$relationKey]     = $targetRelation;
        $targetConfig->set('relation', $targetRelations);
        $this->configManager->persist($targetConfig);
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
