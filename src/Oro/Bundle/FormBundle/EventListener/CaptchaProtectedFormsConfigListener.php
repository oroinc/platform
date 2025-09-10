<?php

namespace Oro\Bundle\FormBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;

/**
 * Config listener that add scope_restrictions to form context that needs to filter choices by configuration scope
 */
class CaptchaProtectedFormsConfigListener
{
    public function setConfig(ConfigSettingsFormOptionsEvent $event): void
    {
        $key = Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS);
        if (!$event->hasFormOptions($key)) {
            return;
        }

        $options = array_merge(
            $event->getFormOptions($key),
            [
                'target_field_options' => [
                    'scope' => $event->getConfigManager()->getScopeEntityName(),
                ]
            ]
        );

        $event->setFormOptions($key, $options);
    }
}
