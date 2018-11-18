<?php

namespace Oro\Bundle\ImapBundle\Exception;

use Oro\Bundle\ImapBundle\Mail\Storage\Message;

/**
 * Exception that contains array with invalid headers exceptions array and message object.
 */
class InvalidMessageHeadersException extends \Exception
{
    /** @var InvalidHeaderException[] */
    private $exceptions;

    /** @var Message */
    private $emailMessage;

    /**
     * @param InvalidHeaderException[] $exceptions
     * @param Message                  $emailMessage
     */
    public function __construct(array $exceptions, Message $emailMessage)
    {
        $this->exceptions = $exceptions;
        $this->emailMessage = $emailMessage;
    }

    /**
     * @return Message
     */
    public function getEmailMessage(): Message
    {
        return $this->emailMessage;
    }

    /**
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
