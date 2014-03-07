<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;

class EmailNotification implements EmailNotificationInterface
{
    /**
     * Constructor
     *
     * @param EmailTemplateInterface $template
     * @param                        $toEmail
     */
    public function __construct(EmailTemplateInterface $template, $toEmail)
    {
        $this->template = $template;
        $this->recipients = array($toEmail);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate($locale = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {

    }
}
