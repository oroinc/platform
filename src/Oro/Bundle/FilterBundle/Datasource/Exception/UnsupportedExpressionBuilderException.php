<?php

namespace Oro\Bundle\FilterBundle\Datasource\Exception;

class UnsupportedExpressionBuilderException extends \LogicException
{
    /**
     * @param string $message
     */
    public function __construct($message = '')
    {
        if (empty($message)) {
            $message = 'The SegmentFilter supports ORM data source only.';
        }
        parent::__construct($message);
    }
}
