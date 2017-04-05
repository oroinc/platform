<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

class ConfigSettingsListener
{
    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onBeforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $settings['value'] = rtrim($settings['value'], '/');
        $event->setSettings($settings);
    }
}
