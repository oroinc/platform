<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class EmailNotification implements EmailNotificationInterface, SenderAwareEmailNotificationInterface
{
    /**
     * @var array
     */
    protected $recipientEmails;

    /**
     * @var EmailTemplateInterface
     */
    protected $template;

    /**
     * @var string|null
     */
    private $senderEmail;

    /**
     * @var string|null
     */
    private $senderName;

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

    /**
     * @param null|string $senderEmail
     * @return EmailNotification
     */
    public function setSenderEmail(?string $senderEmail): EmailNotification
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    /**
     * @param null|string $senderName
     * @return EmailNotification
     */
    public function setSenderName(?string $senderName): EmailNotification
    {
        $this->senderName = $senderName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    /**
     * @return null|string
     */
    public function getSenderName(): ?string
    {
        return $this->senderName;
    }
}
