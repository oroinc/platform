<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Sends an email using specified transport.
 */
class Mailer implements MailerInterface
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        $sentMessage = $this->transport->send($message, $envelope);
        // copy the Message ID header to be able to detect emails sent from ORO side and to avoid duplicates.
        if ($message instanceof Message) {
            $headers = $message->getHeaders();
            // Store the transport-provided Message-ID in a custom header.
            // Some transports may return identifiers that don't comply with RFC 2822.
            $headers->addTextHeader('X-Oro-Message-ID', $sentMessage->getMessageId());
            $message->setHeaders($headers);
        }
    }
}
