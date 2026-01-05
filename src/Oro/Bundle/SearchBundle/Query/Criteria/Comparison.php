<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;

class Comparison extends BaseComparison
{
    public const NOT_CONTAINS  = 'NOT CONTAINS';
    public const STARTS_WITH = 'STARTS WITH';
    public const EXISTS  = 'EXISTS';
    public const NOT_EXISTS  = 'NOT EXISTS';
    public const LIKE = 'LIKE';
    public const NOT_LIKE = 'NOT LIKE';

    /** @var array */
    public static $filteringOperators = [
        self::EXISTS,
        self::NOT_EXISTS,
    ];
}
