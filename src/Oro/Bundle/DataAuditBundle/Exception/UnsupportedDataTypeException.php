<?php

namespace Oro\Bundle\DataAuditBundle\Exception;

/**
 * Thrown when attempting to audit a field with an unsupported data type.
 *
 * The data audit system supports specific field types (string, numeric, date, array, etc.) that can be
 * properly stored and displayed in audit logs. This exception is raised when encountering a field type
 * that cannot be handled by the current audit field type registry, indicating that a custom audit field
 * type handler may need to be registered for the data type.
 */
class UnsupportedDataTypeException extends \InvalidArgumentException implements Exception
{
    /**
     * @param string $dataType
     */
    public function __construct($dataType)
    {
        $message = sprintf('Unsupported audit data type "%s"', $dataType);

        parent::__construct($message);
    }
}
