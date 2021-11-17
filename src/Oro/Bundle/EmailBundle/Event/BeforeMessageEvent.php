<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before email is passed to mailer transport.
 */
class BeforeMessageEvent extends Event
{
    private RawMessage $message;
    private ?Envelope $envelope;

    public function __construct(RawMessage $message, Envelope $envelope = null)
    {
        $this->message = $message;
        $this->envelope = $envelope;
    }

    public function getMessage(): RawMessage
    {
        return $this->message;
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->envelope;
    }

    public function setEnvelope(?Envelope $envelope): self
    {
        $this->envelope = $envelope;

        return $this;
    }
}
