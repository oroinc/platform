<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\SecurityPolicyInspector;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateProvider;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSecurityPolicy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Runs Twig sandbox security policy validation against one or all email templates
 * stored in the database and returns structured inspection results.
 *
 * Delegates the actual validation to the Symfony Validator component
 * using the {@see EmailTemplateSecurityPolicy} constraint.
 */
class EmailTemplateSecurityPolicyInspector implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EmailTemplateProvider $emailTemplateProvider,
        private readonly ValidatorInterface $validator,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Inspects a single email template by name.
     *
     * Returns null when no template with the given name exists in the database.
     */
    public function inspectByName(
        EmailTemplateCriteria|string $templateName
    ): ?EmailTemplateSecurityPolicyInspectionResult {
        $this->logger->debug(
            'Inspecting email template "{name}" against the security policy.',
            ['name' => $templateName]
        );

        $template = $this->emailTemplateProvider->loadEmailTemplate($templateName);
        if ($template === null) {
            $this->logger->warning(
                'Email template "{name}" not found, skipping security policy inspection.',
                ['name' => $templateName]
            );

            return null;
        }

        $result = $this->doInspect($template);

        $this->logger->debug(
            'Security policy inspection of email template "{name}" completed, found {violations} violation(s).',
            ['name' => $templateName, 'violations' => count($result->getViolations())]
        );

        return $result;
    }

    /**
     * Inspects all email templates stored in the database.
     *
     * @return list<EmailTemplateSecurityPolicyInspectionResult>
     */
    public function inspectAll(): array
    {
        $templates = $this->doctrine->getRepository(EmailTemplate::class)->findAll();

        $this->logger->debug(
            'Inspecting {count} email template(s) against the security policy.',
            ['count' => count($templates)]
        );

        $results = [];
        foreach ($templates as $template) {
            $results[] = $this->doInspect($template);
        }

        return $results;
    }

    private function doInspect(EmailTemplateInterface $emailTemplate): EmailTemplateSecurityPolicyInspectionResult
    {
        $violations = $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());

        return new EmailTemplateSecurityPolicyInspectionResult($emailTemplate, $violations);
    }
}
