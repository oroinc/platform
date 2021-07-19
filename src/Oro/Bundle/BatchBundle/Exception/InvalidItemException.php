<?php

namespace Oro\Bundle\BatchBundle\Exception;

/**
 * Exception throw during step execution when an item is invalid.
 */
class InvalidItemException extends \Exception
{
    private array $item;

    private array $messageParameters;

    public function __construct(
        string $message,
        array $item,
        array $messageParameters = [],
        int $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->item              = $item;
        $this->messageParameters = $messageParameters;
    }

    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }

    public function getItem(): array
    {
        return $this->item;
    }
}
