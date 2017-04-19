<?php

namespace Oro\Bundle\ReportBundle\Exception;

class InvalidDatagridConfigException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Invalid datagrid configuration provided';
        }

        parent::__construct($message, $code, $previous);
    }
}
