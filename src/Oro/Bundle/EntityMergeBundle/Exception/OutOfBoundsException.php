<?php

namespace Oro\Bundle\EntityMergeBundle\Exception;

/**
 * Thrown when accessing an entity or field at an invalid offset during merge operations.
 *
 * This exception is raised when attempting to access entities by offset in the merge
 * data collection using an invalid or out-of-bounds index.
 */
class OutOfBoundsException extends \OutOfBoundsException implements Exception
{
}
