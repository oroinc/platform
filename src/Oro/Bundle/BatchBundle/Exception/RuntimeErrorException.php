<?php

namespace Oro\Bundle\BatchBundle\Exception;

/**
 * Exception that stops the job execution.
 */
class RuntimeErrorException extends \RuntimeException
{
    private array $messageParameters;

    public function __construct(string $message, array $messageParameters = [])
    {
        parent::__construct($message);

        $this->messageParameters = $messageParameters;
    }

    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }
}
