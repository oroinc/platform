<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before an email template is rendered.
 */
class EmailTemplateRenderBeforeEvent extends Event
{
    private EmailTemplateInterface $emailTemplate;

    private array $templateParams;

    private array $templateContext;

    public function __construct(
        EmailTemplateInterface $emailTemplate,
        array $templateParams = [],
        array $templateContext = []
    ) {
        $this->emailTemplate = $emailTemplate;
        $this->templateParams = $templateParams;
        $this->templateContext = $templateContext;
    }

    public function getEmailTemplate(): EmailTemplateInterface
    {
        return $this->emailTemplate;
    }

    public function getTemplateParams(): array
    {
        return $this->templateParams;
    }

    public function getTemplateContext(): array
    {
        return $this->templateContext;
    }

    public function getTemplateContextParameter(string $name): mixed
    {
        return $this->templateContext[$name] ?? null;
    }
}
