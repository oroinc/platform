<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\SecurityPolicy;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyViolationInterface;

/**
 * Validates an email template against the Twig sandbox security policy.
 */
interface EmailTemplateSecurityPolicyCheckerInterface
{
    /**
     * Validates the email template against the active Twig sandbox security policy.
     *
     * @return list<EmailTemplateSecurityPolicyViolationInterface>
     */
    public function checkSecurityPolicy(EmailTemplateInterface $emailTemplate): array;
}
