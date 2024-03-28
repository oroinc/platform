<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\EmailTemplateCandidates\EmailTemplateCandidatesProviderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Twig\Error\LoaderError;

/**
 * Loads and provides the most relevant email template.
 */
class EmailTemplateProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EmailTemplateLoaderInterface $emailTemplateLoader;

    private EmailTemplateCandidatesProviderInterface $emailTemplateCandidatesProvider;

    public function __construct(
        EmailTemplateLoaderInterface $emailTemplateLoader,
        EmailTemplateCandidatesProviderInterface $emailTemplateCandidatesProvider
    ) {
        $this->emailTemplateLoader = $emailTemplateLoader;
        $this->emailTemplateCandidatesProvider = $emailTemplateCandidatesProvider;

        $this->logger = new NullLogger();
    }

    public function loadEmailTemplate(
        EmailTemplateCriteria|string $templateName,
        array $templateContext = []
    ): ?EmailTemplateModel {
        $emailTemplateCriteria = is_scalar($templateName) ? new EmailTemplateCriteria($templateName) : $templateName;
        $templateNames = $this->emailTemplateCandidatesProvider
            ->getCandidatesNames($emailTemplateCriteria, $templateContext);

        if (!$templateNames) {
            return null;
        }

        try {
            foreach ($templateNames as $name) {
                if ($this->emailTemplateLoader->exists($name)) {
                    return $this->emailTemplateLoader->getEmailTemplate($name);
                }
            }

            throw new LoaderError(
                sprintf('Unable to find one of the following email templates: "%s".', implode('", "', $templateNames))
            );
        } catch (LoaderError $exception) {
            $this->logger->error(
                'Failed to load email template "{name}": {message}',
                [
                    'name' => $emailTemplateCriteria->getName(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

            return null;
        }
    }
}
