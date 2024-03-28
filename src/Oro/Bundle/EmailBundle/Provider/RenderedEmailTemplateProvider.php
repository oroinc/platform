<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Finds the most relevant email template for a specified email template criteria and provides a rendered one.
 */
class RenderedEmailTemplateProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EmailTemplateProvider $emailTemplateProvider;

    private EmailRenderer $emailRenderer;

    private TranslatedEmailTemplateProvider $translatedEmailTemplateProvider;

    private LocalizationProviderInterface $localizationProvider;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EmailTemplateProvider $emailTemplateProvider,
        EmailRenderer $emailRenderer
    ) {
        $this->emailTemplateProvider = $emailTemplateProvider;
        $this->emailRenderer = $emailRenderer;

        $this->logger = new NullLogger();
    }

    public function findAndRenderEmailTemplate(
        EmailTemplateCriteria|string $templateName,
        array $templateParams = [],
        array $templateContext = []
    ): EmailTemplateInterface {
        $emailTemplateModel = $this->emailTemplateProvider->loadEmailTemplate($templateName, $templateContext);
        if ($emailTemplateModel === null) {
            if ($templateName instanceof EmailTemplateCriteria) {
                $name = $templateName->getName();
                $entityName = $templateName->getEntityName();
            } else {
                $name = $templateName;
                $entityName = '';
            }

            $this->logger->error(
                'Could not find email template for the given criteria',
                [
                    'templateName' => $name,
                    'entityName' => $entityName,
                    'templateContext' => $templateContext,
                ]
            );

            throw new EmailTemplateNotFoundException($templateName);
        }

        return $this->emailRenderer->renderEmailTemplate($emailTemplateModel, $templateParams, $templateContext);
    }
}
