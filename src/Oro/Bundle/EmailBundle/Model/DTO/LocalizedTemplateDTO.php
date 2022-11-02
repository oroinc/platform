<?php

namespace Oro\Bundle\EmailBundle\Model\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;

/**
 * DTO model for relate template model to recipients
 */
class LocalizedTemplateDTO
{
    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var EmailHolderInterface[] */
    private $recipients;

    public function __construct(EmailTemplate $emailTemplate)
    {
        $this->emailTemplate = $emailTemplate;
    }

    public function getEmailTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }

    /**
     * @return EmailHolderInterface[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return string[]
     */
    public function getEmails(): array
    {
        $emails = [];
        foreach ($this->recipients as $recipient) {
            $emails[] = $recipient->getEmail();
        }

        return $emails;
    }

    /**
     * @param EmailHolderInterface $recipient
     * @return LocalizedTemplateDTO
     */
    public function addRecipient(EmailHolderInterface $recipient): self
    {
        $this->recipients[] = $recipient;
        return $this;
    }
}
