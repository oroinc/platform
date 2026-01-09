<?php

namespace Oro\Bundle\ActivityBundle\Exception;

/**
 * Thrown when an invalid argument is passed to an activity-related operation.
 *
 * This exception is raised when activity methods receive arguments that do not meet
 * the expected requirements, such as invalid entity classes, incompatible activity types,
 * or malformed activity associations. It helps developers identify and fix incorrect
 * usage of the ActivityBundle API.
 */
class InvalidArgumentException extends \Exception implements Exception
{
}
