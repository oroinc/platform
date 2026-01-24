<?php

namespace Oro\Bundle\ReminderBundle\Exception;

/**
 * Thrown when a method is not supported for a particular operation or context.
 *
 * This exception indicates that a requested method or operation is not available
 * or applicable in the current context, such as when a reminder sender or processor
 * does not support a specific functionality.
 */
class MethodNotSupportedException extends \InvalidArgumentException implements Exception
{
}
