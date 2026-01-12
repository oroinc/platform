<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when action or condition configuration is invalid.
 *
 * This exception is raised when the configuration provided to an action or condition
 * is malformed, incomplete, or contains invalid values that prevent proper initialization.
 */
class InvalidConfigurationException extends InvalidArgumentException
{
}
