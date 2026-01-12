<?php

namespace Oro\Bundle\GaufretteBundle\Exception;

use Gaufrette\Exception;

/**
 * Thrown when the Gaufrette protocol stream wrapper is not properly configured.
 *
 * This exception is raised when the application attempts to use Gaufrette's stream wrapper
 * functionality but the required configuration (`knp_gaufrette.stream_wrapper`) is missing or incomplete.
 * Developers should ensure that the Gaufrette stream wrapper is properly configured in the
 * application's configuration files before using Gaufrette-based file operations.
 */
class ProtocolConfigurationException extends \RuntimeException implements Exception
{
    public function __construct()
    {
        parent::__construct(
            'The Gaufrette protocol is not configured. Make sure knp_gaufrette.stream_wrapper is configured.'
        );
    }
}
