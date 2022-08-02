<?php

namespace Oro\Bundle\NotificationBundle\Mailer;

use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\ExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Sends an email using specified transport.
 * Dispatches {@see NotificationSentEvent}.
 */
class MassNotificationsMailer implements MailerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private TransportInterface $transport;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(TransportInterface $transport, EventDispatcherInterface $eventDispatcher)
    {
        $this->transport = $transport;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new NullLogger();
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        try {
            $sentMessage = $this->transport->send($message, $envelope);

            $sentCount = count($sentMessage->getEnvelope()->getRecipients());
            // copy the Message ID header to be able to correct detect sent from the ORO side emails
            // and avoid duplicates.
            if ($message instanceof Message) {
                $headers = $message->getHeaders();
                $headers->addIdHeader('Message-ID', $sentMessage->getMessageId());
                $message->setHeaders($headers);
            }
        } catch (ExceptionInterface $transportException) {
            $sentCount = 0;

            $this->logger->error(
                sprintf(
                    'Failed to send a mass notification message %s: %s',
                    get_debug_type($message),
                    $transportException->getMessage()
                ),
                ['exception' => $transportException]
            );
        }

        $this->eventDispatcher->dispatch(
            new NotificationSentEvent($message, $sentCount, MassNotificationSender::NOTIFICATION_LOG_TYPE)
        );
    }
}
