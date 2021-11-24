<?php

namespace Oro\Bundle\ImapBundle\Mail\Protocol\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when an email format is invalid.
 */
class InvalidEmailFormatException extends RuntimeException
{
    private array $collectedData;

    public function __construct(
        array $collectedData,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->collectedData = $collectedData;
        parent::__construct($message, $code, $previous);
    }

    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
}
