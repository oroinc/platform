<?php

namespace Oro\Bundle\DashboardBundle\Exception;

/**
 * Thrown when an invalid argument is provided to dashboard operations.
 *
 * This exception is used throughout the dashboard bundle to signal that a method
 * or function received an argument that does not meet its requirements, such as
 * invalid widget names, malformed configuration data, or unsupported option values.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
