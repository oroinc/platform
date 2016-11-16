<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class EmailNotification implements EmailNotificationInterface
{
    /** @var array */
    protected $recipientEmails;

    /** @var EmailTemplateInterface */
    protected $template;

    /**
     * @param EmailTemplateInterface $template
     * @param array $recipientEmails
     */
    public function __construct(EmailTemplateInterface $template, array $recipientEmails = [])
    {
        $this->template = $template;
        $this->recipientEmails = $recipientEmails;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param EmailTemplateInterface $template
     */
    public function setTemplate(EmailTemplateInterface $template)
    {
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        return $this->recipientEmails;
    }

    /**
     * Sets recipients
     *
     * @param array $recipientEmails
     */
    public function setRecipientEmails(array $recipientEmails)
    {
        $this->recipientEmails = $recipientEmails;
    }

    /**
     * Add a single recipient email
     *
     * @param string $recipientEmail
     */
    public function addRecipientEmail($recipientEmail)
    {
        $this->recipientEmails[] = $recipientEmail;
    }
}
