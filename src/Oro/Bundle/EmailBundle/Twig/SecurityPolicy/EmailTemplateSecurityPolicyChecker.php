<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\SecurityPolicy;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyCheckBefore;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateMetadataProvider;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFilterViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFunctionViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyMethodViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyPropertyViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyTagViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyViolationInterface;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\TemplateSecurityPolicyChecker;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFilterViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFunctionViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyMethodViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyPropertyViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyTagViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyViolationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Validates an email template against the Twig sandbox security policy.
 *
 * Checks each configured template field (default: 'subject' and 'content') for:
 * - Disallowed tags, filters, and functions (compile-time check via Twig sandbox).
 *   Only the first violation per field is reported - Twig throws on the first disallowed element.
 * - Disallowed property and method accesses on the associated entity (via static template analysis).
 *   All such violations across the field are reported.
 *
 * The set of fields to check can be changed at any time via {@see setEmailTemplateFields()}.
 * Returns an empty list when the sandbox extension is not registered or no violations are found.
 */
class EmailTemplateSecurityPolicyChecker implements EmailTemplateSecurityPolicyCheckerInterface
{
    /** @var list<string> */
    private array $emailTemplateFields = ['subject', 'content'];

    public function __construct(
        private readonly TemplateSecurityPolicyChecker $templateSecurityPolicyChecker,
        private readonly EmailTemplateMetadataProvider $emailTemplateMetadataProvider,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Overrides the list of email template fields to validate.
     * Each element must be a field name for which the {@see EmailTemplateInterface} exposes
     * a getter method named get{Ucfirst(fieldName)}() (e.g. 'subject' -> getSubject()).
     *
     * @param list<string> $emailTemplateFields
     */
    public function setEmailTemplateFields(array $emailTemplateFields): void
    {
        $this->emailTemplateFields = $emailTemplateFields;
    }

    /**
     * Validates the email template against the active Twig sandbox security policy.
     *
     * @return list<EmailTemplateSecurityPolicyViolationInterface>
     */
    #[\Override]
    public function checkSecurityPolicy(EmailTemplateInterface $emailTemplate): array
    {
        $entityClass = $this->resolveEntityClass($emailTemplate);
        $variableTypes = $entityClass !== null ? ['entity' => $entityClass] : [];
        $variableTypes = $this->dispatchBeforeEvent($emailTemplate, $variableTypes);

        $violations = [];

        foreach ($this->emailTemplateFields as $templateField) {
            $templateSource = $this->getFieldContent($emailTemplate, $templateField);
            $securityPolicyViolations = $this->templateSecurityPolicyChecker
                ->checkSecurityPolicy($templateSource, $variableTypes);

            foreach ($securityPolicyViolations as $violation) {
                $violations[] = $this->wrapViolation($violation, $templateField);
            }
        }

        return $violations;
    }

    /**
     * Wraps a generic security policy violation with email-template-specific context
     * (the name of the email template field where the violation was detected).
     */
    private function wrapViolation(
        SecurityPolicyViolationInterface $violation,
        string $templateField,
    ): EmailTemplateSecurityPolicyViolationInterface {
        switch (true) {
            case $violation instanceof SecurityPolicyTagViolation:
                return new EmailTemplateSecurityPolicyTagViolation(
                    name: $violation->getName(),
                    templateLine: $violation->getTemplateLine(),
                    cause: $violation->getCause(),
                    templateField: $templateField,
                );
            case $violation instanceof SecurityPolicyFilterViolation:
                return new EmailTemplateSecurityPolicyFilterViolation(
                    name: $violation->getName(),
                    templateLine: $violation->getTemplateLine(),
                    cause: $violation->getCause(),
                    templateField: $templateField,
                );
            case $violation instanceof SecurityPolicyFunctionViolation:
                return new EmailTemplateSecurityPolicyFunctionViolation(
                    name: $violation->getName(),
                    templateLine: $violation->getTemplateLine(),
                    cause: $violation->getCause(),
                    templateField: $templateField,
                );
            case $violation instanceof SecurityPolicyPropertyViolation:
                return new EmailTemplateSecurityPolicyPropertyViolation(
                    name: $violation->getName(),
                    variableName: $violation->getVariableName(),
                    entityClass: $violation->getEntityClass(),
                    templateLine: $violation->getTemplateLine(),
                    cause: $violation->getCause(),
                    templateField: $templateField,
                );
            case $violation instanceof SecurityPolicyMethodViolation:
                return new EmailTemplateSecurityPolicyMethodViolation(
                    name: $violation->getName(),
                    variableName: $violation->getVariableName(),
                    entityClass: $violation->getEntityClass(),
                    templateLine: $violation->getTemplateLine(),
                    cause: $violation->getCause(),
                    templateField: $templateField,
                );
            default:
                throw new \UnexpectedValueException(
                    sprintf(
                        'Unexpected security policy violation type "%s".',
                        get_class($violation),
                    )
                );
        }
    }

    /**
     * Extracts template field content by calling the corresponding getter on the email template.
     * The getter is derived by convention: field 'subject' -> getSubject(), 'content' -> getContent().
     *
     * @throws \InvalidArgumentException When the email template has no getter for the given field.
     */
    private function getFieldContent(EmailTemplateInterface $emailTemplate, string $field): string
    {
        $getter = 'get' . ucfirst($field);
        if (!method_exists($emailTemplate, $getter)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Email template does not have a getter for field "%s". Expected method: "%s()".',
                    $field,
                    $getter,
                )
            );
        }

        $value = $emailTemplate->{$getter}();

        return \is_string($value) ? $value : '';
    }

    /**
     * @param EmailTemplateInterface $emailTemplate
     * @param array<string,string> $variableTypes
     *
     * @return array<string,string>
     */
    private function dispatchBeforeEvent(EmailTemplateInterface $emailTemplate, array $variableTypes): array
    {
        $event = new EmailTemplateSecurityPolicyCheckBefore(
            emailTemplate: $emailTemplate,
            variableTypes: $variableTypes
        );
        $this->eventDispatcher->dispatch($event);

        return $event->getVariableTypes();
    }

    private function resolveEntityClass(EmailTemplateInterface $emailTemplate): ?string
    {
        // Resolve entity class directly from the template when available (e.g., during form validation
        // of a new template that has not been persisted yet and cannot be found by the metadata provider).
        if (($emailTemplate instanceof EmailTemplateEntity || $emailTemplate instanceof EmailTemplateModel) &&
            $emailTemplate->getEntityName() !== null) {
            return $emailTemplate->getEntityName();
        }

        $metadata = $this->emailTemplateMetadataProvider->getEmailTemplateMetadata($emailTemplate);
        if ($metadata === null) {
            return null;
        }

        return $metadata[EmailTemplateMetadataProvider::ENTITY_NAME] ?? null;
    }
}
