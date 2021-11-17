<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\BeforeMessageEvent;
use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport;

/**
 * Adds message headers required to send email via {@see UserEmailOriginTransport}.
 */
class SetUserEmailOriginTransportListener
{
    private string $userEmailOriginTransportName;

    public function __construct(string $userEmailOriginTransportName)
    {
        $this->userEmailOriginTransportName = $userEmailOriginTransportName;
    }

    public function onBeforeMessage(BeforeMessageEvent $event): void
    {
        $envelope = $event->getEnvelope();
        if (!$envelope instanceof EmailOriginAwareEnvelope) {
            return;
        }

        $emailOrigin = $envelope->getEmailOrigin();
        if (!$emailOrigin instanceof UserEmailOrigin) {
            return;
        }

        if (!$emailOrigin->isSmtpConfigured()) {
            return;
        }

        $symfonyEmail = $event->getMessage();
        $symfonyEmail->getHeaders()
            ->addHeader('X-Transport', $this->userEmailOriginTransportName)
            ->addHeader(UserEmailOriginTransport::HEADER_NAME, $emailOrigin->getId());
    }
}
