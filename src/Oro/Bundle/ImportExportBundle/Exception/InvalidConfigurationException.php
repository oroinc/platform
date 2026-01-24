<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when import/export configuration is invalid or incomplete.
 *
 * This exception is raised when required configuration options are missing,
 * have invalid values, or are incompatible with the current operation context.
 */
class InvalidConfigurationException extends InvalidArgumentException
{
}
