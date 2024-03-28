<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after na email template is rendered.
 */
class EmailTemplateRenderAfterEvent extends Event
{
    private EmailTemplateInterface $emailTemplate;

    private ?EmailTemplateInterface $renderedEmailTemplate;

    private array $templateParams;

    private array $templateContext;

    public function __construct(
        EmailTemplateInterface $emailTemplate,
        ?EmailTemplateInterface $renderedEmailTemplate,
        array $templateParams = [],
        array $templateContext = []
    ) {
        $this->emailTemplate = $emailTemplate;
        $this->renderedEmailTemplate = $renderedEmailTemplate;
        $this->templateParams = $templateParams;
        $this->templateContext = $templateContext;
    }

    public function getEmailTemplate(): EmailTemplateInterface
    {
        return $this->emailTemplate;
    }

    public function getRenderedEmailTemplate(): ?EmailTemplateInterface
    {
        return $this->renderedEmailTemplate;
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
