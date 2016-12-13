<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;

class SmtpSettingsChangeListener
{
    /**
     * @param ConfigUpdateEvent $event
     */
    public function onSmtpConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged(Config::KEY_SMTP_SETTINGS_HOST)
            && !$event->isChanged(Config::KEY_SMTP_SETTINGS_PORT)
            && !$event->isChanged(Config::KEY_SMTP_SETTINGS_ENC)
            && !$event->isChanged(Config::KEY_SMTP_SETTINGS_USER)
            && !$event->isChanged(Config::KEY_SMTP_SETTINGS_PASS)) {
            return;
        }

        // implement test connection
    }
}
