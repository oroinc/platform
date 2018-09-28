<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * Provides possibility to get simple notification info such as email template conditions and recipient objects
 */
class TemplateEmailNotification extends EmailNotification implements TemplateEmailNotificationInterface
{
    /**
     * @var string
     */
    private $templateName;

    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @var EmailHolderInterface[]
     */
    private $recipients;

    /**
     * @param EmailTemplate $template
     * @param EmailHolderInterface[] $recipients
     */
    public function __construct(EmailTemplate $template, array $recipients)
    {
        $emails = [];
        foreach ($recipients as $recipient) {
            $emails[] = $recipient->getEmail();
        }

        parent::__construct($template, array_unique($emails));
        $this->templateName = $template->getName();
        $this->entityClassName = $template->getEntityName();
        $this->recipients = $recipients;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateCriteria(): EmailTemplateCriteria
    {
        return new EmailTemplateCriteria($this->templateName, $this->entityClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(): iterable
    {
        return $this->recipients;
    }
}
