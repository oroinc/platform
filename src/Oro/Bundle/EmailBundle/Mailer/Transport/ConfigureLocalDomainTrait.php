<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Adds common logic of handling local domain for SMTP transports.
 */
trait ConfigureLocalDomainTrait
{
    private function configureLocalDomain(TransportInterface $transport, ?RequestStack $requestStack): void
    {
        if (!is_a($transport, SmtpTransport::class)) {
            return;
        }

        $host = $requestStack?->getCurrentRequest()?->server->get('HTTP_HOST');
        // Fixes local domain when wild-card vhost is used and auto-detection fails.
        if (!empty($host) && str_starts_with($transport->getLocalDomain(), '*')) {
            $transport->setLocalDomain($host);
        }
    }
}
