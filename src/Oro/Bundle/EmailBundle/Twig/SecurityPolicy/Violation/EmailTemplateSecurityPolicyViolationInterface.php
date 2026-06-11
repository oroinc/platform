<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation;

use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyViolationInterface;

/**
 * Extends the generic {@see SecurityPolicyViolationInterface} with email-template-specific context:
 * the name of the email template field (e.g. 'subject' or 'content') where the violation was found.
 */
interface EmailTemplateSecurityPolicyViolationInterface extends SecurityPolicyViolationInterface
{
    /**
     * Returns the name of the email template field where the violation was detected
     * (e.g. 'subject', 'content', or any other field configured on the checker).
     */
    public function getTemplateField(): string;
}
