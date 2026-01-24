<?php

namespace Oro\Bundle\EntityBundle\Exception;

/**
 * Thrown when a user attempts to update a field they do not have access to.
 *
 * This exception indicates that the current user lacks the necessary permissions
 * to modify the specified entity field.
 */
class FieldUpdateAccessException extends \Exception
{
}
