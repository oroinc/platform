<?php

namespace Oro\Bundle\EntityBundle\Exception;

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
