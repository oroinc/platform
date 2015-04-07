<?php

namespace Oro\Bundle\FilterBundle\Datasource\Exception;

class UnsupportedExpressionBuilderException extends \LogicException
{
    public function __construct()
    {
        parent::__construct('The SegmentFilter supports ORM data source only.');
    }
}
