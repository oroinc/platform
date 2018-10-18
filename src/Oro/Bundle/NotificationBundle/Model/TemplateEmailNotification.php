<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;

/**
 * Provides possibility to get simple notification info such as email template conditions and recipient objects
 */
class TemplateEmailNotification implements TemplateEmailNotificationInterface, SenderAwareInterface
{
    /**
     * @var EmailTemplateCriteria
     */
    private $emailTemplateCriteria;

    /**
     * @var EmailHolderInterface[]
     */
    private $recipients;

    /**
     * @var object
     */
    private $entity;

    /**
     * @var From
     */
    private $sender;

    /**
     * @param EmailTemplateCriteria $emailTemplateCriteria
     * @param iterable $recipients
     * @param object|null $entity
     * @param From|null $sender
     */
    public function __construct(
        EmailTemplateCriteria $emailTemplateCriteria,
        iterable $recipients,
        $entity = null,
        From $sender = null
    ) {
        $this->emailTemplateCriteria = $emailTemplateCriteria;
        $this->recipients = $recipients;
        $this->entity = $entity;
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateCriteria(): EmailTemplateCriteria
    {
        return $this->emailTemplateCriteria;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(): iterable
    {
        return $this->recipients;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getSender(): ?From
    {
        return $this->sender;
    }
}
