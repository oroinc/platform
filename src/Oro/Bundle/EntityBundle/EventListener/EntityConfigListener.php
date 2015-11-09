<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;

class EntityConfigListener
{
    /**
     * @param FieldConfigEvent $event
     */
    public function createField(FieldConfigEvent $event)
    {
        $configProvider = $event->getConfigManager()->getProvider('entity');
        $config         = $configProvider->getConfig($event->getClassName(), $event->getFieldName());
        if (!$config->is('label')) {
            $config->set('label', $event->getFieldName());
        }
    }
}
