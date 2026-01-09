<?php

namespace Oro\Bundle\ReportBundle\Exception;

/**
 * Thrown when a datagrid configuration is invalid or malformed.
 *
 * This exception is raised during datagrid configuration processing when the provided
 * configuration does not meet the expected structure or requirements. It provides a
 * default error message if none is supplied, making it easier to identify configuration
 * issues in report and datagrid processing.
 */
class InvalidDatagridConfigException extends \Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Invalid datagrid configuration provided';
        }

        parent::__construct($message, $code, $previous);
    }
}
