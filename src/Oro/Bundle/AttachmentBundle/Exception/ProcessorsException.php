<?php

namespace Oro\Bundle\AttachmentBundle\Exception;

/**
 * Exception for invalid processors library.
 */
class ProcessorsException extends \RuntimeException
{
    public function __construct(string $name)
    {
        $message = sprintf('The %s library not found or not executable.', $name);

        parent::__construct($message);
    }
}
