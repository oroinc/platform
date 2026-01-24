<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;

/**
 * Handles entity field configuration events.
 *
 * This listener automatically sets default labels for newly created entity fields
 * if no label has been explicitly configured. It ensures that all fields have
 * meaningful labels in the entity configuration.
 */
class EntityConfigListener
{
    public function createField(FieldConfigEvent $event)
    {
        $configProvider = $event->getConfigManager()->getProvider('entity');
        $config         = $configProvider->getConfig($event->getClassName(), $event->getFieldName());
        if (!$config->is('label')) {
            $config->set('label', $event->getFieldName());
        }
    }
}
