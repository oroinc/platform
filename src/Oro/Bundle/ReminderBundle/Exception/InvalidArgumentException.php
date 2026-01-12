<?php

namespace Oro\Bundle\ReminderBundle\Exception;

/**
 * Thrown when an invalid argument is passed to a reminder bundle method.
 *
 * This exception indicates that a method received an argument that does not meet
 * the expected requirements or constraints, such as invalid data types, out-of-range
 * values, or arguments that violate business logic rules.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
