<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent;
use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateRenderingContext;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error;

/**
 * Renders email template as TWIG template in a sandboxed environment.
 */
class EmailRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private TemplateRenderer $templateRenderer;

    private ?EmailTemplateAttachmentProcessor $emailTemplateAttachmentProcessor = null;

    private TwigEnvironment $twigEnvironment;

    private EmailTemplateRenderingContext $emailTemplateRenderingContext;

    private EventDispatcherInterface $eventDispatcher;

    private PropertyAccessorInterface $propertyAccessor;

    private HtmlTagHelper $htmlTagHelper;

    private array $renderableFields = ['subject', 'content'];

    public function __construct(
        TemplateRenderer $templateRenderer,
        TwigEnvironment $twigEnvironment,
        EmailTemplateRenderingContext $emailTemplateRenderingContext,
        EventDispatcherInterface $eventDispatcher,
        PropertyAccessorInterface $propertyAccessor,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->twigEnvironment = $twigEnvironment;
        $this->emailTemplateRenderingContext = $emailTemplateRenderingContext;
        $this->eventDispatcher = $eventDispatcher;
        $this->propertyAccessor = $propertyAccessor;
        $this->htmlTagHelper = $htmlTagHelper;

        $this->logger = new NullLogger();
    }

    public function setEmailTemplateAttachmentProcessor(
        ?EmailTemplateAttachmentProcessor $emailTemplateAttachmentProcessor
    ): void {
        $this->emailTemplateAttachmentProcessor = $emailTemplateAttachmentProcessor;
    }

    public function setRenderableFields(array $renderableFields): void
    {
        $this->renderableFields = $renderableFields;
    }

    public function renderEmailTemplate(
        EmailTemplateInterface $emailTemplate,
        array $templateParams = [],
        array $templateContext = []
    ): EmailTemplateInterface {
        try {
            $this->emailTemplateRenderingContext->fillFromArray($templateContext);

            $event = new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext);
            $this->eventDispatcher->dispatch($event);

            $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
                ->setType($emailTemplate->getType());

            foreach ($this->renderableFields as $fieldName) {
                $fieldValue = $this->propertyAccessor->getValue($emailTemplate, $fieldName);
                if ($fieldValue) {
                    $rendered = $this->templateRenderer->renderTemplate($fieldValue, $templateParams);
                    $this->propertyAccessor->setValue($renderedEmailTemplate, $fieldName, $rendered);
                }
            }

            // BC layer.
            if (!$this->emailTemplateAttachmentProcessor) {
                // Do nothing.
            } else {
                foreach ($emailTemplate->getAttachments() as $emailTemplateAttachment) {
                    $processedAttachment = $this->emailTemplateAttachmentProcessor
                        ->processAttachment($emailTemplateAttachment, $templateParams);

                    if ($processedAttachment) {
                        $renderedEmailTemplate->addAttachment($processedAttachment);
                    }
                }
            }
        } catch (Error $exception) {
            $this->logger->error(
                'Rendering of email template "{email_template_name}" failed. {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                    'email_template_name' => $emailTemplate->getName(),
                    'email_template' => $emailTemplate,
                ]
            );

            throw new EmailTemplateCompilationException($emailTemplate, $exception);
        } finally {
            $event = new EmailTemplateRenderAfterEvent(
                $emailTemplate,
                $renderedEmailTemplate ?? null,
                $templateParams,
                $templateContext
            );
            $this->eventDispatcher->dispatch($event);

            $this->emailTemplateRenderingContext->clear();
        }

        return $renderedEmailTemplate;
    }

    /**
     * Compiles the given email template.
     *
     * @param EmailTemplateInterface $template
     * @param array $templateParams
     *
     * @return array [email subject, email body]
     *
     * @throws \Twig\Error\Error if the given template cannot be compiled
     */
    public function compileMessage(EmailTemplateInterface $template, array $templateParams = []): array
    {
        $subjectTemplate = $template->getSubject();
        $contentTemplate = $template->getContent();

        $subject = $subjectTemplate ? $this->templateRenderer->renderTemplate($subjectTemplate, $templateParams) : '';
        $content = $contentTemplate ? $this->templateRenderer->renderTemplate($contentTemplate, $templateParams) : '';

        return [$subject, $content];
    }

    /**
     * Compiles the given email template for the preview purposes.
     *
     * @throws \Twig\Error\Error if the given template cannot be compiled
     */
    public function compilePreview(EmailTemplateInterface $template): string
    {
        $content = $this->htmlTagHelper->sanitize($template->getContent(), 'default', false);

        return $this->twigEnvironment
            ->createTemplate('{% verbatim %}' . $content . '{% endverbatim %}')
            ->render();
    }

    public function renderTemplate(string $templateContent, array $templateParams = []): string
    {
        return $this->templateRenderer->renderTemplate($templateContent, $templateParams);
    }

    public function validateTemplate(string $templateContent, array $templateParams = []): void
    {
        $this->templateRenderer->validateTemplate($templateContent);
    }

    public function getCurrentTemplateContext(): array
    {
        return $this->emailTemplateRenderingContext->toArray();
    }
}
