<?php

namespace Oro\Component\PhpUtils\Exception;

/**
 * This exception is thrown when unserialization of data failed.
 */
class UnsafeUnserializationException extends \RuntimeException
{
    public static function create(array $notAllowedClasses): self
    {
        return new self(
            sprintf('Data passed to unserialize contains not allowed classes: %s', implode(', ', $notAllowedClasses))
        );
    }
}
