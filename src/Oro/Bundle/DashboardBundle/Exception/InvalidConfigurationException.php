<?php

namespace Oro\Bundle\DashboardBundle\Exception;

/**
 * Thrown when dashboard or widget configuration cannot be found or is invalid.
 *
 * This exception is raised when attempting to access configuration for a widget or
 * dashboard component that does not exist or when the configuration structure does
 * not meet the expected format. It helps identify configuration issues during
 * development and provides clear error messages for troubleshooting.
 */
class InvalidConfigurationException extends \Exception implements Exception
{
    public function __construct($message = "")
    {
        $message = 'Can\'t find configuration for: ' . $message;
        parent::__construct($message);
    }
}
