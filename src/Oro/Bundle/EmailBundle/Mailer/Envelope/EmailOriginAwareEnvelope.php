<?php

namespace Oro\Bundle\EmailBundle\Mailer\Envelope;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\LogicException;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\RawMessage;

/**
 * Mailer envelope that can additionally hold {@see EmailOrigin} entity.
 */
class EmailOriginAwareEnvelope extends Envelope
{
    private Envelope $envelope;

    private ?EmailOrigin $emailOrigin;

    public function __construct(RawMessage $message, EmailOrigin $emailOrigin = null)
    {
        $this->envelope = parent::create($message);
        $this->emailOrigin = $emailOrigin;
    }

    public function setSender(SymfonyAddress $sender): void
    {
        $this->envelope->setSender($sender);
    }

    public function getSender(): SymfonyAddress
    {
        return $this->envelope->getSender();
    }

    public function setRecipients(array $recipients): void
    {
        $this->envelope->setRecipients($recipients);
    }

    public function getRecipients(): array
    {
        return $this->envelope->getRecipients();
    }

    public function setEmailOrigin(?EmailOrigin $emailOrigin): void
    {
        $this->emailOrigin = $emailOrigin;
    }

    public function getEmailOrigin(): ?EmailOrigin
    {
        return $this->emailOrigin;
    }

    /**
     * @see \Symfony\Component\Mailer\Envelope::create()
     */
    public static function create(RawMessage $message): self
    {
        if (\get_class($message) === RawMessage::class) {
            throw new LogicException('Cannot send a RawMessage instance without an explicit Envelope.');
        }

        return new self($message);
    }
}
