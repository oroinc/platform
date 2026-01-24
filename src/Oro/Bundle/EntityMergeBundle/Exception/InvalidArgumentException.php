<?php

namespace Oro\Bundle\EntityMergeBundle\Exception;

/**
 * Thrown when an invalid argument is passed to a merge operation or related method.
 *
 * This exception is raised when merge-related methods receive arguments that do not
 * meet the expected type, value, or state requirements, such as invalid field names,
 * missing metadata, or incompatible entity types.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
