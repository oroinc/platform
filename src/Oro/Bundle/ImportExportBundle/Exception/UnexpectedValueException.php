<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when an unexpected value is encountered during import/export processing.
 *
 * This exception is raised when a value does not match expected criteria, such as
 * when a processor or configuration with a given alias cannot be found in the registry.
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{
}
