<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

/**
 * Provides possibility to get mass notification info such as email template conditions and recipient objects
 */
class TemplateMassNotification extends MassNotification implements TemplateEmailNotificationInterface
{
    /**
     * @var EmailTemplateCriteria
     */
    private $emailTemplateCriteria;

    /**
     * @var EmailHolderInterface[]
     */
    private $recipientObjects;

    /**
     * @var string|null
     */
    private $subject;

    /**
     * @param string $senderName
     * @param string $senderEmail
     * @param iterable|EmailHolderInterface[] $recipientObjects
     * @param EmailTemplateInterface $template
     * @param EmailTemplateCriteria $emailTemplateCriteria
     * @param string|null $subject
     */
    public function __construct(
        string $senderName,
        string $senderEmail,
        iterable $recipientObjects,
        EmailTemplateInterface $template,
        EmailTemplateCriteria $emailTemplateCriteria,
        ?string $subject = null
    ) {
        $emails = [];
        foreach ($recipientObjects as $recipientObject) {
            $emails[] = $recipientObject->getEmail();
        }

        parent::__construct($senderName, $senderEmail, array_unique($emails), $template);
        $this->recipientObjects = $recipientObjects;
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
        return $this->recipientObjects;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }
}
