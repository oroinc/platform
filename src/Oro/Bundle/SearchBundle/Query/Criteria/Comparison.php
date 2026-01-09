<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;

/**
 * Search criteria comparison with extended operators for advanced filtering and existence checks
 */
class Comparison extends BaseComparison
{
    public const NOT_CONTAINS  = 'NOT CONTAINS';
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
