<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when an invalid argument is provided to an action or condition.
 *
 * This exception indicates that a required argument is missing, has an invalid type,
 * or does not meet the expected constraints for action or condition initialization.
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
