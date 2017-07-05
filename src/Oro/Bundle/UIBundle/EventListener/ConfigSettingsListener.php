<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UIBundle\DependencyInjection\Configuration as UIConfiguration;

class ConfigSettingsListener
{
    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onBeforeSave(ConfigSettingsUpdateEvent $event)
    {
        $appUrlConfigKey = $this->getApplicationUrlConfigKey();
        $settings = $event->getSettings();

        if (!array_key_exists($appUrlConfigKey, $settings)) {
            return;
        }

        $appUrlSettings = $settings[$appUrlConfigKey];
        $appUrlSettings['value'] = rtrim($appUrlSettings['value'], '/');
        $settings[$appUrlConfigKey] = $appUrlSettings;
        $event->setSettings($settings);
    }

    /**
     * @return string
     */
    protected function getApplicationUrlConfigKey()
    {
        return UIConfiguration::getFullConfigKey(UIConfiguration::APPLICATION_URL_KEY);
    }
}
