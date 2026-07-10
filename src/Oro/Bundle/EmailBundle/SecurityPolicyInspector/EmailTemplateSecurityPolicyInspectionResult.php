<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\SecurityPolicyInspector;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Holds the result of a security policy inspection for a single email template,
 * combining the template model with the list of constraint violations found during validation.
 */
final class EmailTemplateSecurityPolicyInspectionResult
{
    public function __construct(
        private readonly EmailTemplateInterface $emailTemplate,
        private readonly ConstraintViolationListInterface $violations,
    ) {
    }

    public function getEmailTemplate(): EmailTemplateInterface
    {
        return $this->emailTemplate;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return count($this->violations) > 0;
    }
}
