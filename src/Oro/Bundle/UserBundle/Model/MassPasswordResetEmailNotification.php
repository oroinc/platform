<?php

namespace Oro\Bundle\UserBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Processor\SenderAwareEmailNotificationInterface;

class MassPasswordResetEmailNotification implements EmailNotificationInterface, SenderAwareEmailNotificationInterface
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var  array */
    protected $emails = [];

    /** @var  EmailTemplateInterface */
    protected $template = null;

    /** @var  string */
    protected $senderEmail = '';

    /** @var  string */
    protected $senderName = '';

    /**
     * Gets a template can be used to prepare a notification message
     *
     * @return EmailTemplateInterface
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
     * Gets a list of email addresses can be used to send a notification message
     *
     * @return string[]
     */
    public function getRecipientEmails()
    {
        return $this->emails;
    }

    /**
     * @param string $email
     */
    public function addEmail($email)
    {
        $this->emails[] = $email;
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @param string $senderEmail
     */
    public function setSenderEmail($senderEmail)
    {
        $this->senderEmail = $senderEmail;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
    }
}
