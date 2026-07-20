<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation;

use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyTagViolation;

/**
 * Email-template-aware tag violation. Extends the generic {@see SecurityPolicyTagViolation}
 * with the name of the email template field where the violation was detected.
 */
final class EmailTemplateSecurityPolicyTagViolation extends SecurityPolicyTagViolation implements
    EmailTemplateSecurityPolicyViolationInterface
{
    public function __construct(
        string $name,
        int $templateLine,
        \Throwable $cause,
        private readonly string $templateField,
    ) {
        parent::__construct(
            name: $name,
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
