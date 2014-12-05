<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

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

        $change       = $event->getConfigManager()->getConfigChangeSet($eventConfig);
        $stateChanged = isset($change['state']);
        $isCustom     = $eventConfig->is('owner', ExtendScope::OWNER_CUSTOM);

        // synchronize field state with entity state, when custom field state changed
        if ($isCustom && 'extend' == $scope && $stateChanged) {
            $configManager = $event->getConfigManager();
            $className     = $eventConfig->getId()->getClassName();
            $entityConfig  = $configManager->getProvider($scope)->getConfig($className);

            if ($entityConfig->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE])) {
                $entityConfig->set('state', ExtendScope::STATE_UPDATE);
                $configManager->persist($entityConfig);
            }
        }
    }

    /**
     * @param EntityConfigEvent $event
     */
    public function updateEntityConfig(EntityConfigEvent $event)
    {
        $className = $event->getClassName();
        $parentClassName = get_parent_class($className);
        if (!$parentClassName) {
            return;
        }

        if (ExtendHelper::isExtendEntityProxy($parentClassName)) {
            // When application is installed parent class will be replaced (via class_alias)
            $extendClass = $parentClassName;
        } else {
            // During install parent class is not replaced (via class_alias)
            $shortClassName = ExtendHelper::getShortClassName($event->getClassName());
            if (ExtendHelper::getShortClassName($parentClassName) !== 'Extend' . $shortClassName) {
                return;
            }
            $extendClass = ExtendHelper::getExtendEntityProxyClassName($parentClassName);
        }

        $config = $event->getConfigManager()->getProvider('extend')->getConfig($event->getClassName());
        $hasChanges = false;
        if (!$config->is('is_extend')) {
            $config->set('is_extend', true);
            $hasChanges = true;
        }
        if (!$config->is('extend_class', $extendClass)) {
            $config->set('extend_class', $extendClass);
            $hasChanges = true;
        }
        if ($hasChanges) {
            $event->getConfigManager()->persist($config);
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
