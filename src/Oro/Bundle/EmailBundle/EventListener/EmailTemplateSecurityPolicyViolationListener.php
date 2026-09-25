<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Psr\Log\LoggerInterface;

/**
 * Logs a Twig sandbox security policy violation caught while an email template was being rendered.
 *
 * Runs at the lowest priority so that it observes the final decision: a violation another listener already
 * handled by substituting a value is deliberate and is not logged.
 */
final class EmailTemplateSecurityPolicyViolationListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function onSecurityPolicyViolation(EmailTemplateSecurityPolicyViolationEvent $event): void
    {
        // An is-defined test only probes for the attribute, so a denial there is an expected answer, not a failure.
        if ($event->isHandled() || $event->isDefinedTest()) {
            return;
        }

        $this->logger->error(
            'Twig security policy exception caught during email template rendering: '
            . $event->getViolation()->getMessage(),
            ['exception' => $event->getViolation()]
        );
    }
}
