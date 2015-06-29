<?php

namespace Oro\Bundle\DataAuditBundle\Exception;

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
