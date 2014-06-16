<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PERSIST_CONFIG     => 'persistConfig',
            Events::NEW_ENTITY_CONFIG      => 'updateEntityConfig',
            Events::UPDATE_ENTITY_CONFIG   => 'updateEntityConfig',
            Events::NEW_FIELD_CONFIG       => 'newFieldConfig',
            Events::RENAME_FIELD           => 'renameField',
        ];
    }

    /**
     * @param PersistConfigEvent $event
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $eventConfigId = $event->getConfig()->getId();
        $scope         = $eventConfigId->getScope();

        if (!$eventConfigId instanceof FieldConfigId) {
            return;
        }

        $change   = $event->getConfigManager()->getConfigChangeSet($eventConfig);
        $sizeMark = count(array_intersect_key(array_flip(['length', 'precision', 'scale', 'state']), $change)) > 0;
        $isCustom = $eventConfig->is('owner', ExtendScope::OWNER_CUSTOM);

        if ('extend' == $scope && $sizeMark && $isCustom) {
            $this->persistCustomFieldConfig($event);
        }

        if ('datagrid' == $scope && $eventConfigId->getFieldType() != 'text') {
            $this->persistExtendConfig($event);
        }
    }

    /**
     * @param PersistConfigEvent $event
     */
    protected function persistCustomFieldConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $configManager = $event->getConfigManager();
        $change        = $configManager->getConfigChangeSet($eventConfig);

        /** @var FieldConfigId $configId */
        $configId     = $eventConfig->getId();
        $scope        = $configId->getScope();
        $className    = $configId->getClassName();
        $entityConfig = $event->getConfigManager()->getProvider($scope)->getConfig($className);

        if ($eventConfig->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATED])
            && !isset($change['state'])
        ) {
            $eventConfig->set('state', ExtendScope::STATE_UPDATED);
            $event->getConfigManager()->calculateConfigChangeSet($eventConfig);
        }

        /**
         * Relations case
         */
        if ($eventConfig->is('state', ExtendScope::STATE_NEW)
            && in_array($configId->getFieldType(), ['oneToMany', 'manyToOne', 'manyToMany'])
        ) {
            $this->createRelation($eventConfig);
        }

        if (!$entityConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATED])) {
            $entityConfig->set('state', ExtendScope::STATE_UPDATED);
            $configManager->persist($entityConfig);
        }
    }

    /**
     * @param PersistConfigEvent $event
     */
    protected function persistExtendConfig(PersistConfigEvent $event)
    {
        /** @var FieldConfigId $eventConfigId */
        $eventConfigId = $event->getConfig()->getId();
        $className     = $eventConfigId->getClassName();

        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $event->getConfigManager()->getProvider('extend');
        $extendFieldConfig    = $extendConfigProvider->getConfigById($eventConfigId);

        if (!$extendFieldConfig->is('is_extend') && !$extendFieldConfig->is('extend')) {
            return;
        }

        $index        = [];
        $extendConfig = $extendConfigProvider->getConfig($className);
        if ($extendConfig->has('index')) {
            $index = $extendConfig->get('index');
        }

        if (!isset($index[$eventConfigId->getFieldName()])
            || $index[$eventConfigId->getFieldName()] != $event->getConfig()->get('is_visible')
        ) {
            $index[$eventConfigId->getFieldName()] = $event->getConfig()->get('is_visible');

            $extendConfig->set('index', $index);

            if (!$extendConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATED])) {
                $extendConfig->set('state', ExtendScope::STATE_UPDATED);
            }

            if (!$extendFieldConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATED])) {
                $extendFieldConfig->set('state', ExtendScope::STATE_UPDATED);
                $event->getConfigManager()->persist($extendFieldConfig);
            }

            $event->getConfigManager()->persist($extendConfig);
        }
    }

    /**
     * @param EntityConfigEvent $event
     */
    public function updateEntityConfig(EntityConfigEvent $event)
    {
        $originalClassName       = $event->getClassName();
        $originalParentClassName = get_parent_class($originalClassName);

        $parentClassArray = explode('\\', $originalParentClassName);
        $classArray       = explode('\\', $originalClassName);

        $parentClassName = array_pop($parentClassArray);
        $className       = array_pop($classArray);

        if ($parentClassName == 'Extend' . $className) {
            $config = $event->getConfigManager()->getProvider('extend')->getConfig($event->getClassName());
            $hasChanges = false;
            if (!$config->is('is_extend')) {
                $config->set('is_extend', true);
                $hasChanges = true;
            }
            $extendClass = ExtendConfigDumper::ENTITY . $parentClassName;
            if (!$config->is('extend_class', $extendClass)) {
                $config->set('extend_class', $extendClass);
                $hasChanges = true;
            }
            if ($hasChanges) {
                $event->getConfigManager()->persist($config);
            }
        }
    }

    /**
     * @param FieldConfigEvent $event
     */
    public function newFieldConfig(FieldConfigEvent $event)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $event->getConfigManager()->getProvider('extend');

        $entityConfig = $configProvider->getConfig($event->getClassName());
        if ($entityConfig->is('upgradeable', false)) {
            $entityConfig->set('upgradeable', true);
            $configProvider->persist($entityConfig);
        }
    }

    /**
     * @param RenameFieldEvent $event
     */
    public function renameField(RenameFieldEvent $event)
    {
        $extendEntityConfig = $event->getConfigManager()->getProvider('extend')->getConfig($event->getClassName());
        if ($extendEntityConfig->has('index')) {
            $index = $extendEntityConfig->get('index');
            if (isset($index[$event->getFieldName()])) {
                $index[$event->getNewFieldName()] = $index[$event->getFieldName()];
                unset($index[$event->getFieldName()]);
                $extendEntityConfig->set('index', $index);
                $event->getConfigManager()->persist($extendEntityConfig);
            }
        }
    }

    protected function isInverseSideRelationExist(Config $fieldConfig)
    {
        $selfConfig = $this->extendConfigProvider->getConfig($fieldConfig->getId()->getClassName());
        if ($selfConfig->has('relation')) {
            $selfRelations  = $selfConfig->get('relation');
            foreach ($selfRelations as $relation) {
                if ($relation['field_id'] == $fieldConfig->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

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

    protected function createSelfRelation(Config $fieldConfig)
    {
        $selfEntityClass   = $fieldConfig->getId()->getClassName();
        $targetEntityClass = $fieldConfig->get('target_entity');
        $selfFieldType     = $fieldConfig->getId()->getFieldType();
        $selfFieldName     = $fieldConfig->getId()->getFieldName();
        $selfConfig        = $this->extendConfigProvider->getConfig($selfEntityClass);
        $relationKey       = ExtendHelper::buildRelationKey(
            $selfEntityClass,
            $selfFieldName,
            $selfFieldType,
            $targetEntityClass
        );
        $scope             = 'extend';

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
}
