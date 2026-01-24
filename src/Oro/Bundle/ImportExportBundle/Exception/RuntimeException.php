<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when an unexpected error occurs during import/export execution.
 *
 * This exception is raised for runtime errors that occur during the execution of
 * import/export jobs, such as file I/O errors, database errors, or other unexpected
 * conditions that prevent the operation from completing successfully.
 */
class RuntimeException extends \RuntimeException implements Exception
{
}
