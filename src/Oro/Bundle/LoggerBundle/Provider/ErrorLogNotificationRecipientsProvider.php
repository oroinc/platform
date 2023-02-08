<?php

namespace Oro\Bundle\LoggerBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;

/**
 * Provides email addresses of recipients for error log email notification.
 */
class ErrorLogNotificationRecipientsProvider
{
    private ConfigManager $configManager;

    private ApplicationState $applicationState;

    public function __construct(ConfigManager $configManager, ApplicationState $applicationState)
    {
        $this->configManager = $configManager;
        $this->applicationState = $applicationState;
    }

    /**
     * @return string[]
     */
    public function getRecipientsEmailAddresses(): array
    {
        if (!$this->applicationState->isInstalled()) {
            return [];
        }

        $recipients = (string)$this->configManager->get(
            Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS)
        );

        return array_filter(array_map('trim', explode(';', $recipients)));
    }
}
