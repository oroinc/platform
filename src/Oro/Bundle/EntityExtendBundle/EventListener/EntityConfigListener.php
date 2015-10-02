<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EntityConfigListener
{
    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('extend');
        if (null === $config || $event->isEntityConfig()) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet     = $configManager->getConfigChangeSet($config);
        // synchronize field state with entity state, when custom field state changed
        if (isset($changeSet['state'])
            && $changeSet['state'][1] !== ExtendScope::STATE_ACTIVE
            && $config->is('owner', ExtendScope::OWNER_CUSTOM)
        ) {
            $entityConfig = $configManager->getEntityConfig('extend', $config->getId()->getClassName());
            if ($entityConfig->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE])) {
                $entityConfig->set('state', ExtendScope::STATE_UPDATE);
                $configManager->persist($entityConfig);
                $configManager->calculateConfigChangeSet($entityConfig);
            }
        }
    }

    /**
     * @param EntityConfigEvent $event
     */
    public function updateEntity(EntityConfigEvent $event)
    {
        $className       = $event->getClassName();
        $parentClassName = get_parent_class($className);
        if (!$parentClassName) {
            return;
        }

        if (ExtendHelper::isExtendEntityProxy($parentClassName)) {
            // When application is installed parent class will be replaced (via class_alias)
            $extendClass = $parentClassName;
        } else {
            // During install parent class is not replaced (via class_alias)
            $shortClassName = ExtendHelper::getShortClassName($className);
            if (ExtendHelper::getShortClassName($parentClassName) !== 'Extend' . $shortClassName) {
                return;
            }
            $extendClass = ExtendHelper::getExtendEntityProxyClassName($parentClassName);
        }

        $configManager = $event->getConfigManager();
        $config        = $configManager->getProvider('extend')->getConfig($className);
        $hasChanges    = false;
        if (!$config->is('is_extend')) {
            $config->set('is_extend', true);
            $hasChanges = true;
        }
        if (!$config->is('extend_class', $extendClass)) {
            $config->set('extend_class', $extendClass);
            $hasChanges = true;
        }
        if ($hasChanges) {
            $configManager->persist($config);
        }
    }

    /**
     * @param FieldConfigEvent $event
     */
    public function createField(FieldConfigEvent $event)
    {
        $configManager = $event->getConfigManager();
        $entityConfig  = $configManager->getProvider('extend')->getConfig($event->getClassName());
        if ($entityConfig->is('upgradeable', false)) {
            $entityConfig->set('upgradeable', true);
            $configManager->persist($entityConfig);
        }
    }

    /**
     * @param RenameFieldEvent $event
     */
    public function renameField(RenameFieldEvent $event)
    {
        $configManager = $event->getConfigManager();
        $entityConfig  = $configManager->getProvider('extend')->getConfig($event->getClassName());
        if ($entityConfig->has('index')) {
            $index = $entityConfig->get('index');
            if (isset($index[$event->getFieldName()])) {
                $index[$event->getNewFieldName()] = $index[$event->getFieldName()];
                unset($index[$event->getFieldName()]);
                $entityConfig->set('index', $index);
                $configManager->persist($entityConfig);
            }
        }
    }
}
