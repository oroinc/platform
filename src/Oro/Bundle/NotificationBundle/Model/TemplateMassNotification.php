<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;

/**
 * Provides possibility to get mass notification info such as email template conditions and recipient objects
 */
class TemplateMassNotification implements TemplateEmailNotificationInterface, SenderAwareInterface
{
    /**
     * @var From
     */
    private $sender;

    /**
     * @var EmailTemplateCriteria
     */
    private $emailTemplateCriteria;

    /**
     * @var EmailHolderInterface[]
     */
    private $recipients;

    /**
     * @var string|null
     */
    private $subject;

    /**
     * @param From $sender
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $emailTemplateCriteria
     * @param string|null $subject
     */
    public function __construct(
        From $sender,
        iterable $recipients,
        EmailTemplateCriteria $emailTemplateCriteria,
        ?string $subject = null
    ) {
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->emailTemplateCriteria = $emailTemplateCriteria;
        $this->subject = $subject;
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
        return null;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * {@inheritdoc}
     */
    public function getSender(): ?From
    {
        return $this->sender;
    }
}
