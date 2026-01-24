<?php

namespace Oro\Bundle\EntityConfigBundle\Exception;

/**
 * Thrown when a runtime error occurs in entity configuration operations.
 *
 * This exception indicates an error that occurs during the execution of entity configuration operations,
 * such as database access failures, configuration loading errors, or other runtime issues that prevent
 * normal operation of the configuration system.
 */
class RuntimeException extends \RuntimeException
{
}
