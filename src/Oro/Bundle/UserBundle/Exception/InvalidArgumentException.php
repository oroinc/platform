<?php

namespace Oro\Bundle\UserBundle\Exception;

/**
 * Thrown when an invalid argument is provided to a UserBundle operation.
 *
 * This exception extends PHP's {@see \InvalidArgumentException} and implements the
 * UserBundle {@see Exception} interface, providing a consistent exception type for
 * argument validation errors within the UserBundle.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
