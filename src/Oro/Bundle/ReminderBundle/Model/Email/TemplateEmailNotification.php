<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;

/**
 * Provides a possibility to get the reminder email notification data
 */
class TemplateEmailNotification extends EmailNotification implements TemplateEmailNotificationInterface
{
    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException|RuntimeException
     */
    public function getTemplateCriteria(): EmailTemplateCriteria
    {
        $className = $this->getReminder()->getRelatedEntityClassName();
        $templateName = $this->configProvider->getConfig($className)->get(self::CONFIG_FIELD, true);

        return new EmailTemplateCriteria($templateName, $className);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function getRecipients(): iterable
    {
        return [$this->getReminder()->getRecipient()];
    }
}
