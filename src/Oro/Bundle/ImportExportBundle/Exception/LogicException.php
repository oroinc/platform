<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when an operation violates the expected logic or state of the import/export system.
 *
 * This exception is raised when an operation cannot be performed due to an invalid state,
 * such as attempting to register a duplicate processor or accessing an entity that does not exist.
 */
class LogicException extends \LogicException implements Exception
{
}
