<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

use Oro\Bundle\FilterBundle\Provider\DatevariablesInterface;

class Lexer
{
    /** @var array */
    protected $allowedExpressionsWithTokens = [
        DatevariablesInterface::VAR_THIS_DAY,
        DatevariablesInterface::VAR_THIS_WEEK,
        DatevariablesInterface::VAR_THIS_MONTH,
        DatevariablesInterface::VAR_THIS_QUARTER,
        DatevariablesInterface::VAR_THIS_YEAR,
        DatevariablesInterface::VAR_FDQ,
        DatevariablesInterface::VAR_FMQ,
    ];

    const VARIABLE_REGEXP = '#{{(\d+)}}#';
    const OPERATOR_REGEXP = '#\+|\-#A';

    public function tokenize($string)
    {

    }

    protected function lexExpression()
    {

    }
}
