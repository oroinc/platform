<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

/**
 * Handles configuration settings update events.
 *
 * Normalizes configuration values by removing trailing slashes from the application URL
 * before the settings are persisted. This ensures consistent URL formatting across the system.
 */
class ConfigSettingsListener
{
    public function onBeforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $settings['value'] = rtrim($settings['value'], '/');
        $event->setSettings($settings);
    }
}
