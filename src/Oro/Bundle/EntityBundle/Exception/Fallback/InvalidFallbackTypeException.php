<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;

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
