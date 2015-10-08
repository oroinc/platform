<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigManager   $configManager
     * @param FieldTypeHelper $fieldTypeHelper
     */
    public function __construct(ConfigManager $configManager, FieldTypeHelper $fieldTypeHelper)
    {
        $this->configManager        = $configManager;
        $this->fieldTypeHelper      = $fieldTypeHelper;
        $this->extendConfigProvider = $configManager->getProvider('extend');
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
        $entityConfigs = $this->extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $fieldConfigs = $this->extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
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
            $this->createSelfRelation($fieldConfig);
        } else {
            $relationKey   = $fieldConfig->get('relation_key');
            $selfConfig    = $this->extendConfigProvider->getConfig($fieldConfig->getId()->getClassName());
            $selfRelations = $selfConfig->get('relation', false, []);
            if (!isset($selfRelations[$relationKey])) {
                $this->createSelfRelation($fieldConfig);
            }
            $this->ensureReverseRelationCompleted($fieldConfig);
        }
    }

    /**
     * @param ConfigInterface $fieldConfig
     */
    protected function createSelfRelation(ConfigInterface $fieldConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId     = $fieldConfig->getId();
        $targetEntityClass = $fieldConfig->get('target_entity');
        $selfFieldId       = $selfFieldId = new FieldConfigId(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType())
        );

        $targetFieldId = false;
        $owner         = true;
        $targetOwner   = false;
        if (in_array($selfFieldId->getFieldType(), RelationType::$toManyRelations, true)) {
            $classNameArray    = explode('\\', $selfFieldId->getClassName());
            $relationFieldName = strtolower(array_pop($classNameArray)) . '_' . $selfFieldId->getFieldName();

            if ($selfFieldId->getFieldType() === RelationType::ONE_TO_MANY) {
                $owner       = false;
                $targetOwner = true;
            }

            $targetFieldId = new FieldConfigId(
                'extend',
                $targetEntityClass,
                $relationFieldName,
                ExtendHelper::getReverseRelationType($selfFieldId->getFieldType())
            );
        }

        $relationKey = ExtendHelper::buildRelationKey(
            $selfFieldId->getClassName(),
            $selfFieldId->getFieldName(),
            $selfFieldId->getFieldType(),
            $targetEntityClass
        );

        $selfConfig         = $this->extendConfigProvider->getConfig($selfFieldId->getClassName());
        $selfRelationConfig = [
            'field_id'        => $selfFieldId,
            'owner'           => $owner,
            'target_entity'   => $targetEntityClass,
            'target_field_id' => $targetFieldId
        ];
        if ($fieldConfig->has('cascade')) {
            $selfRelationConfig['cascade'] = $fieldConfig->get('cascade');
        }
        $selfRelations               = $selfConfig->get('relation', false, []);
        $selfRelations[$relationKey] = $selfRelationConfig;
        $selfConfig->set('relation', $selfRelations);
        $this->configManager->persist($selfConfig);

        $targetConfig                  = $this->extendConfigProvider->getConfig($targetEntityClass);
        $targetRelationConfig          = [
            'field_id'        => $targetFieldId,
            'owner'           => $targetOwner,
            'target_entity'   => $selfFieldId->getClassName(),
            'target_field_id' => $selfFieldId,
        ];
        $targetRelations               = $targetConfig->get('relation', false, []);
        $targetRelations[$relationKey] = $targetRelationConfig;
        $targetConfig->set('relation', $targetRelations);
        $this->configManager->persist($targetConfig);

        $fieldConfig->set('relation_key', $relationKey);
        $this->configManager->persist($fieldConfig);
    }

    /**
     * Makes sure that both source and target entities know about a reverse relation
     *
     * @param ConfigInterface $fieldConfig
     */
    protected function ensureReverseRelationCompleted(ConfigInterface $fieldConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $fieldConfig->getId();

        $relationKey   = $fieldConfig->get('relation_key');
        $selfConfig    = $this->extendConfigProvider->getConfig($fieldConfigId->getClassName());
        $selfRelations = $selfConfig->get('relation', false, []);
        if (isset($selfRelations[$relationKey]['field_id']) && $selfRelations[$relationKey]['field_id']) {
            return;
        }

        $targetConfig    = $this->extendConfigProvider->getConfig($fieldConfig->get('target_entity'));
        $targetRelations = $targetConfig->get('relation', false, []);
        if (!isset($targetRelations[$relationKey])) {
            return;
        }

        $selfFieldId = new FieldConfigId(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType())
        );

        $selfRelations[$relationKey]['field_id']          = $selfFieldId;
        $targetRelations[$relationKey]['target_field_id'] = $selfFieldId;

        $selfConfig->set('relation', $selfRelations);
        $targetConfig->set('relation', $targetRelations);

        $this->configManager->persist($selfConfig);
        $this->configManager->persist($targetConfig);
    }
}
