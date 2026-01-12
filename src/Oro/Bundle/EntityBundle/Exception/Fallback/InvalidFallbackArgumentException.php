<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

/**
 * Thrown when an invalid argument is provided to a fallback provider.
 *
 * This exception indicates that the argument passed to a fallback provider
 * does not match the expected format or type.
 */
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
