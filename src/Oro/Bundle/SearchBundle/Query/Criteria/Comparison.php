<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;

class Comparison extends BaseComparison
{
    const NOT_CONTAINS  = 'NOT CONTAINS';
}
