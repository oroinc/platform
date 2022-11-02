<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Must be dispatched after the email notification is passed to mailer for sending.
 */
class NotificationSentEvent extends Event
{
    private RawMessage $message;

    private int $sentCount;

    private string $type;

    public function __construct(RawMessage $message, int $sentCount, string $type)
    {
        $this->message = $message;
        $this->sentCount = $sentCount;
        $this->type = $type;
    }

    public function getMessage(): RawMessage
    {
        return $this->message;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
