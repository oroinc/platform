<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Oro\Bundle\EmailBundle\Event\BeforeMessageEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\RawMessage;

/**
 * Adds extra functionality to the decorated transport:
 * 1 Dispatches {@see BeforeMessageEvent} event before message and envelope are passed to the decorated transport.
 * 2 Adds logging in case of errors
 */
class Transport implements TransportInterface, LoggerAwareInterface
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

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        try {
            $event = new BeforeMessageEvent($message, $envelope);
            $this->eventDispatcher->dispatch($event);
            $envelope = $event->getEnvelope();

            $sentMessage = $this->transport->send($message, $envelope);
        } catch (\Throwable $throwable) {
            // Envelope cannot be created from a RawMessage, see \Symfony\Component\Mailer\Envelope::create().
            if (!$envelope && \get_class($message) !== RawMessage::class) {
                $envelope = Envelope::create($message);
            }

            $context = ['message' => $message, 'exception' => $throwable];

            if ($envelope) {
                $context['sender'] = $envelope->getSender()->toString();
                $context['recipients'] = array_map(
                    static fn (SymfonyAddress $address) => $address->toString(),
                    $envelope->getRecipients()
                );
            }

            $this->logger->error(
                sprintf('Failed to send a %s message: %s', get_debug_type($message), $throwable->getMessage()),
                $context
            );

            throw $throwable;
        }

        return $sentMessage;
    }

    public function __toString(): string
    {
        return $this->transport;
    }
}
