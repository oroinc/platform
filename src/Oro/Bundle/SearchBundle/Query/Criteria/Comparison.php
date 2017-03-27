<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;

class Comparison extends BaseComparison
{
    const NOT_CONTAINS  = 'NOT CONTAINS';
    const STARTS_WITH = 'STARTS WITH';
    const EXISTS  = 'EXISTS';
    const NOT_EXISTS  = 'NOT EXISTS';
    const LIKE = 'LIKE';
    const NOT_LIKE = 'NOT LIKE';

    /** @var array */
    public static $filteringOperators = [
        self::EXISTS,
        self::NOT_EXISTS,
    ];
}
