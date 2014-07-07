<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

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
     * Update configs depending on their data and persist
     * - create relations config data
     * - update entity and field states
     * - create index config data
     *
     * @param PersistConfigEvent $event
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $eventConfigId = $eventConfig->getId();
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
}
