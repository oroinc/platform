<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

/**
 * Thrown when field value type doesn't match expected type during import denormalization.
 */
class InvalidFieldTypeException extends \RuntimeException implements Exception
{
}
