<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation;

use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyMethodViolation;

/**
 * Email-template-aware method violation. Extends the generic {@see SecurityPolicyMethodViolation}
 * with the name of the email template field where the violation was detected.
 */
final class EmailTemplateSecurityPolicyMethodViolation extends SecurityPolicyMethodViolation implements
    EmailTemplateSecurityPolicyViolationInterface
{
    public function __construct(
        string $name,
        ?string $variableName,
        ?string $entityClass,
        int $templateLine,
        \Throwable $cause,
        private readonly string $templateField,
    ) {
        parent::__construct(
            name: $name,
            variableName: $variableName,
            entityClass: $entityClass,
            templateLine: $templateLine,
            cause: $cause
        );
    }

    #[\Override]
    public function getTemplateField(): string
    {
        return $this->templateField;
    }
}
