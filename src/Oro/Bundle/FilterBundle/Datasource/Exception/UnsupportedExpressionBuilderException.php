<?php

namespace Oro\Bundle\FilterBundle\Datasource\Exception;

class UnsupportedExpressionBuilderException extends \LogicException
{
    /**
     * @param string $className
     * @param string $dataSourceType
     */
    public function __construct($className, $dataSourceType)
    {
        $message = sprintf('The %s supports %s data source only.', $className, $dataSourceType);

        parent::__construct($message);
    }
}
