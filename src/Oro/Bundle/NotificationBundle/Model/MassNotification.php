<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Processor\SenderAwareEmailNotificationInterface;

class MassNotification implements SenderAwareEmailNotificationInterface
{
    /**
     * @var string
     */
    protected $senderName;

    /**
     * @var string
     */
    protected $senderEmail;

    /**
     * @var array
     */
    protected $resipients;

    /**
     * @var EmailNotificationInterface
     */
    protected $template;

    public function __construct($senderName, $senderEmail, $resipients, $template)
    {
        $this->senderName = $senderName;
        $this->senderEmail = $senderEmail;
        $this->resipients = $resipients;
        $this->template = $template;
    }

    /**
     * @inheritdoc
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @inheritdoc
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @inheritdoc
     */
    public function getRecipientEmails()
    {
        return $this->resipients;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
