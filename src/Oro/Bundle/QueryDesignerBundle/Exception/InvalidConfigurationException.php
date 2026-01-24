<?php

namespace Oro\Bundle\QueryDesignerBundle\Exception;

/**
 * Thrown when the query designer configuration is invalid or incomplete.
 *
 * This exception is raised when configuration data provided to the query designer
 * does not meet the required specifications or contains invalid values that prevent
 * proper query construction or execution.
 */
class InvalidConfigurationException extends \RuntimeException
{
}
