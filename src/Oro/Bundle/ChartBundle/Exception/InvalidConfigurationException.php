<?php

namespace Oro\Bundle\ChartBundle\Exception;

/**
 * Thrown when a required chart configuration cannot be found or is invalid.
 *
 * This exception is raised when the ChartBundle attempts to access or use a chart
 * configuration that does not exist or is malformed. The exception message is
 * automatically prefixed with "Can't find configuration for chart: " to provide
 * context about the missing configuration.
 */
class InvalidConfigurationException extends \Exception implements Exception
{
    public function __construct($message = "")
    {
        $message = 'Can\'t find configuration for chart: ' . $message;
        parent::__construct($message);
    }
}
