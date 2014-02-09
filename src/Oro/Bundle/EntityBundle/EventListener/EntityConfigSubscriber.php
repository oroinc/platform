<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;

class EntityConfigSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::NEW_FIELD_CONFIG => 'newFieldConfig'
        );
    }

    /**
     * @param FieldConfigEvent $event
     */
    public function newFieldConfig(FieldConfigEvent $event)
    {
        $configProvider = $event->getConfigManager()->getProvider('entity');
        $config = $configProvider->getConfig($event->getClassName(), $event->getFieldName());
        if (!$config->is('label')) {
            $config->set('label', $event->getFieldName());
        }
    }
}
