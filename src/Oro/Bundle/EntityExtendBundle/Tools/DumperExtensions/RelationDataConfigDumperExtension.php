<?php
namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class RelationDataConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigProviderInterface */
    protected $extendConfigProvider;

    /**
     * @param ConfigManager   $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager        = $configManager;
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
    public function preUpdate(array &$extendConfigs)
    {
        foreach ($extendConfigs as $entityConfig) {
            $className    = $entityConfig->getId()->getClassName();
            $fieldConfigs = $this->extendConfigProvider->getConfigs($className);

            foreach ($fieldConfigs as $fieldConfig) {
                /** @var ConfigInterface $entityConfig */
                if (!$fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                    continue;
                }

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();

                if ($fieldConfig->is('state', ExtendScope::STATE_NEW) &&
                    in_array($fieldConfigId->getFieldType(), ['oneToMany', 'manyToOne', 'manyToMany'])
                ) {
                    $this->createRelation($fieldConfig);
                }
            }
        }
    }

    /**
     * @param Config $fieldConfig
     */
    protected function createRelation(Config $fieldConfig)
    {
        if ($this->isInverseSideRelationExist($fieldConfig)) {
            return;
        }

        $createSelfRelation = true;
        $relationKey        = null;

        if ($fieldConfig->is('relation_key')) {
            $targetConfig    = $this->extendConfigProvider->getConfig($fieldConfig->get('target_entity'));
            $targetRelations = $targetConfig->get('relation');
            $relationKey     = $fieldConfig->get('relation_key');
            if (isset($targetRelations[$relationKey])) {

                $this->createTargetRelation($fieldConfig, $fieldConfig->get('relation_key'));

                if ($targetRelations[$relationKey]['assign']) {
                    $createSelfRelation = false;
                }
            }
        }

        if ($createSelfRelation) {
            $this->createSelfRelation($fieldConfig);
        }
    }

    /**
     * @param Config $fieldConfig
     */
    protected function createSelfRelation(Config $fieldConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId     = $fieldConfig->getId();
        $targetEntityClass = $fieldConfig->get('target_entity');
        $selfEntityClass   = $fieldConfigId->getClassName();
        $selfFieldType     = $fieldConfigId->getFieldType();
        $selfFieldName     = $fieldConfigId->getFieldName();
        $selfConfig        = $this->extendConfigProvider->getConfig($selfEntityClass);
        $scope             = 'extend';

        $relationKey = ExtendHelper::buildRelationKey(
            $selfEntityClass,
            $selfFieldName,
            $selfFieldType,
            $targetEntityClass
        );

        /**
         * in case of oneToMany relation
         * automatically create target field (type: manyToOne)
         */
        $targetFieldId = false;
        $owner         = true;
        $targetOwner   = false;

        if (in_array($selfFieldType, ['oneToMany', 'manyToMany'])) {
            $classNameArray    = explode('\\', $selfEntityClass);
            $relationFieldName = strtolower(array_pop($classNameArray)) . '_' . $selfFieldName;

            if ($selfFieldType == 'oneToMany') {
                $owner       = false;
                $targetOwner = true;
            }

            $targetFieldId = new FieldConfigId(
                $scope,
                $targetEntityClass,
                $relationFieldName,
                ExtendHelper::getReverseRelationType($selfFieldType)
            );
        }

        $selfRelationConfig = [
            'assign'          => false,
            'field_id'        => $fieldConfig->getId(),
            'owner'           => $owner,
            'target_entity'   => $targetEntityClass,
            'target_field_id' => $targetFieldId // for 1:*, create field
        ];

        $selfRelations               = $selfConfig->get('relation') ? : [];
        $selfRelations[$relationKey] = $selfRelationConfig;

        $selfConfig->set('relation', $selfRelations);

        $this->extendConfigProvider->persist($selfConfig);

        $targetConfig = $this->extendConfigProvider->getConfig($targetEntityClass);

        $targetRelationConfig = [
            'assign'          => false,
            'field_id'        => $targetFieldId, // for 1:*, new created field
            'owner'           => $targetOwner,
            'target_entity'   => $selfEntityClass,
            'target_field_id' => $fieldConfig->getId(),
        ];

        $targetRelations               = $targetConfig->get('relation') ? : [];
        $targetRelations[$relationKey] = $targetRelationConfig;

        $targetConfig->set('relation', $targetRelations);
        $fieldConfig->set('relation_key', $relationKey);

        $this->extendConfigProvider->persist($targetConfig);
    }

    /**
     * @param Config $fieldConfig
     * @param string $relationKey
     */
    protected function createTargetRelation(Config $fieldConfig, $relationKey)
    {
        $selfEntityClass   = $fieldConfig->getId()->getClassName();
        $targetEntityClass = $fieldConfig->get('target_entity');

        $selfConfig         = $this->extendConfigProvider->getConfig($selfEntityClass);
        $selfRelations      = $selfConfig->get('relation');
        $selfRelationConfig = & $selfRelations[$relationKey];

        $selfRelationConfig['field_id'] = $fieldConfig->getId();

        $targetConfig         = $this->extendConfigProvider->getConfig($targetEntityClass);
        $targetRelations      = $targetConfig->get('relation');
        $targetRelationConfig = & $targetRelations[$relationKey];

        $targetRelationConfig['target_field_id'] = $fieldConfig->getId();

        $selfConfig->set('relation', $selfRelations);
        $targetConfig->set('relation', $targetRelations);

        $this->extendConfigProvider->persist($targetConfig);
    }

    /**
     * @param Config $fieldConfig
     *
     * @return bool
     */
    protected function isInverseSideRelationExist(Config $fieldConfig)
    {
        $selfConfig = $this->extendConfigProvider->getConfig($fieldConfig->getId()->getClassName());
        if ($selfConfig->has('relation')) {
            $selfRelations = $selfConfig->get('relation');
            foreach ($selfRelations as $relation) {
                if ($relation['field_id'] == $fieldConfig->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
