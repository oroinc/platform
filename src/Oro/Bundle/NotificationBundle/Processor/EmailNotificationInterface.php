<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

/**
 * Provides a way to get some configuration info of an email notification message
 */
interface EmailNotificationInterface
{
    /**
     * Gets a template can be used to prepare a notification message
     *
     * @param null|string $locale
     *
     * @return EmailTemplateInterface
     */
    public function getTemplate($locale = null);

    /**
     * Gets a list of email addresses can be used to send a notification message
     *
     * @return string[]
     */
    public function getRecipientEmails();
}
