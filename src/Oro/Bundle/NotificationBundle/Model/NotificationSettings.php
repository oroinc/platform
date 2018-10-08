<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\From;

/**
 * Provides notification settings data in a handy format.
 */
class NotificationSettings
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return From
     */
    public function getSender(): From
    {
        return From::emailAddress(
            $this->configManager->get('oro_notification.email_notification_sender_email'),
            $this->configManager->get('oro_notification.email_notification_sender_name')
        );
    }

    /**
     * @param object $entity
     * @return From
     */
    public function getSenderByScopeEntity($entity): From
    {
        return From::emailAddress(
            $this->configManager->get('oro_notification.email_notification_sender_email', false, false, $entity),
            $this->configManager->get('oro_notification.email_notification_sender_name', false, false, $entity)
        );
    }

    /**
     * @return string
     */
    public function getMassNotificationEmailTemplateName(): string
    {
        return $this->configManager->get('oro_notification.mass_notification_template');
    }

    /**
     * @return array
     */
    public function getMassNotificationRecipientEmails(): array
    {
        $recipientEmails = $this->configManager->get('oro_notification.mass_notification_recipients');

        if ($recipientEmails) {
            return explode(';', $recipientEmails);
        }

        return [];
    }
}
