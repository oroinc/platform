<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when an invalid argument is provided to an import/export operation.
 *
 * This exception is raised when a method receives an argument that is invalid,
 * such as an unsupported processor alias, invalid entity name, or malformed configuration.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
