<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to collect email template context.
 */
class EmailTemplateContextCollectEvent extends Event
{
    private From $from;

    /**
     * @var array<EmailHolderInterface>
     */
    private array $recipients;

    private EmailTemplateCriteria $emailTemplateCriteria;

    private array $templateParams;

    private array $templateContext = [];

    public function __construct(
        From $from,
        array $recipients,
        EmailTemplateCriteria $emailTemplateCriteria,
        array $templateParams = []
    ) {
        $this->from = $from;
        $this->recipients = $recipients;
        $this->emailTemplateCriteria = $emailTemplateCriteria;
        $this->templateParams = $templateParams;
    }

    public function getFrom(): From
    {
        return $this->from;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getEmailTemplateCriteria(): EmailTemplateCriteria
    {
        return $this->emailTemplateCriteria;
    }

    public function getTemplateParams(): array
    {
        return $this->templateParams;
    }

    public function getTemplateContext(): array
    {
        return $this->templateContext;
    }

    public function setTemplateContext(array $templateContext): self
    {
        $this->templateContext = $templateContext;

        return $this;
    }

    public function getTemplateContextParameter(string $name): mixed
    {
        return $this->templateContext[$name] ?? null;
    }

    public function setTemplateContextParameter(string $name, mixed $value): self
    {
        $this->templateContext[$name] = $value;

        return $this;
    }
}
