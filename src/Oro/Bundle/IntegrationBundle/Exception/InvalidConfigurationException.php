<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Thrown when an integration configuration is invalid or incomplete.
 *
 * This exception is raised when required configuration parameters are missing,
 * have invalid values, or are incompatible with the integration type or transport settings.
 */
class InvalidConfigurationException extends \InvalidArgumentException implements IntegrationException
{
}
