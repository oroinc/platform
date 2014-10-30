<?php
namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class RelationEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ConfigProviderInterface */
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
                if (!$fieldConfig->is('state', ExtendScope::STATE_NEW)) {
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
                if (in_array($fieldConfigId->getFieldType(), ['oneToMany', 'manyToOne', 'manyToMany'])) {
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
        $createSelfRelation = true;

        if ($fieldConfig->is('relation_key')) {
            $relationKey = $fieldConfig->get('relation_key');
            $selfConfig  = $this->extendConfigProvider->getConfig($fieldConfig->getId()->getClassName());
            $selfRelations = $selfConfig->get('relation');
            if (isset($selfRelations[$relationKey]['field_id'])) {
                $createSelfRelation = false;
            } else {
                $targetConfig    = $this->extendConfigProvider->getConfig($fieldConfig->get('target_entity'));
                $targetRelations = $targetConfig->get('relation');
                if (isset($targetRelations[$relationKey])) {
                    $this->createTargetRelation($fieldConfig, $relationKey);
                    if ($targetRelations[$relationKey]['assign']) {
                        $createSelfRelation = false;
                    }
                }
            }
        }

        if ($createSelfRelation) {
            $this->createSelfRelation($fieldConfig);
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
        $selfFieldId       = new FieldConfigId(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType())
        );
        $selfConfig        = $this->extendConfigProvider->getConfig($selfFieldId->getClassName());

        $relationKey = ExtendHelper::buildRelationKey(
            $selfFieldId->getClassName(),
            $selfFieldId->getFieldName(),
            $selfFieldId->getFieldType(),
            $targetEntityClass
        );

        /**
         * in case of oneToMany relation
         * automatically create target field (type: manyToOne)
         */
        $targetFieldId = false;
        $owner         = true;
        $targetOwner   = false;

        if (in_array($selfFieldId->getFieldType(), ['oneToMany', 'manyToMany'])) {
            $classNameArray    = explode('\\', $selfFieldId->getClassName());
            $relationFieldName = strtolower(array_pop($classNameArray)) . '_' . $selfFieldId->getFieldName();

            if ($selfFieldId->getFieldType() === 'oneToMany') {
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

        $selfRelationConfig = [
            'assign'          => false,
            'field_id'        => $selfFieldId,
            'owner'           => $owner,
            'target_entity'   => $targetEntityClass,
            'target_field_id' => $targetFieldId
        ];

        $selfRelations               = $selfConfig->get('relation') ? : [];
        $selfRelations[$relationKey] = $selfRelationConfig;

        $selfConfig->set('relation', $selfRelations);

        $this->extendConfigProvider->persist($selfConfig);

        $targetConfig = $this->extendConfigProvider->getConfig($targetEntityClass);

        $targetRelationConfig = [
            'assign'          => false,
            'field_id'        => $targetFieldId,
            'owner'           => $targetOwner,
            'target_entity'   => $selfFieldId->getClassName(),
            'target_field_id' => $selfFieldId,
        ];

        $targetRelations               = $targetConfig->get('relation') ? : [];
        $targetRelations[$relationKey] = $targetRelationConfig;

        $targetConfig->set('relation', $targetRelations);
        $fieldConfig->set('relation_key', $relationKey);

        $this->extendConfigProvider->persist($targetConfig);
    }

    /**
     * @param ConfigInterface $fieldConfig
     * @param string $relationKey
     */
    protected function createTargetRelation(ConfigInterface $fieldConfig, $relationKey)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId     = $fieldConfig->getId();
        $selfFieldId       = new FieldConfigId(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType())
        );
        $targetEntityClass = $fieldConfig->get('target_entity');

        $selfConfig         = $this->extendConfigProvider->getConfig($selfFieldId->getClassName());
        $selfRelations      = $selfConfig->get('relation');
        $selfRelationConfig = & $selfRelations[$relationKey];

        $selfRelationConfig['field_id'] = $selfFieldId;

        $targetConfig         = $this->extendConfigProvider->getConfig($targetEntityClass);
        $targetRelations      = $targetConfig->get('relation');
        $targetRelationConfig = & $targetRelations[$relationKey];

        $targetRelationConfig['target_field_id'] = $selfFieldId;

        $selfConfig->set('relation', $selfRelations);
        $targetConfig->set('relation', $targetRelations);

        $this->extendConfigProvider->persist($targetConfig);
    }
}
