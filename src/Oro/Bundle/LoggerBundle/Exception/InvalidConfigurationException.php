<?php

namespace Oro\Bundle\LoggerBundle\Exception;

/**
 * Thrown when the logger bundle configuration is invalid or incomplete.
 *
 * This exception is raised during logger bundle initialization or configuration
 * validation when required configuration parameters are missing, malformed, or
 * contain invalid values that prevent proper logger setup.
 */
class InvalidConfigurationException extends \Exception
{
}
