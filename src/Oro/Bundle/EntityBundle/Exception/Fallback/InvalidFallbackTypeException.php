<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;

/**
 * Thrown when an invalid fallback data type is provided.
 *
 * This exception indicates that the fallback type is not one of the allowed types
 * (boolean, integer, decimal, or string) defined in {@see EntityFallbackResolver}.
 */
class InvalidFallbackTypeException extends \Exception
{
    public function __construct($fallbackType)
    {
        $message = sprintf(
            "Invalid fallback data type '%s' provided. Allowed types: '%s'",
            $fallbackType,
            implode(',', EntityFallbackResolver::$allowedTypes)
        );

        parent::__construct($message);
    }
}
