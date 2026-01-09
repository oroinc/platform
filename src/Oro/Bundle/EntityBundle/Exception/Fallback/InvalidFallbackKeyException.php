<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

/**
 * Thrown when an invalid fallback key is provided.
 *
 * This exception indicates that the fallback key does not correspond to any
 * registered fallback provider or configuration.
 */
class InvalidFallbackKeyException extends \Exception
{
    /**
     * @param string $fallbackKey
     */
    public function __construct($fallbackKey)
    {
        $message = sprintf('Invalid fallback key "%s" provided', $fallbackKey);
        parent::__construct($message);
    }
}
