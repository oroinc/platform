<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

class InvalidFallbackArgumentException extends \Exception
{
    /**
     * @param string $argumentInfo
     * @param string $providerName
     */
    public function __construct($argumentInfo, $providerName)
    {
        $message = sprintf('Invalid argument "%s" provided for fallback provider "%s"', $argumentInfo, $providerName);
        parent::__construct($message);
    }
}
