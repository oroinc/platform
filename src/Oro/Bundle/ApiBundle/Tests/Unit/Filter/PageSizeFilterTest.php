<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class PageSizeFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyWithoutFilter()
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $criteria = new Criteria();

        $filter->apply($criteria);

        self::assertNull($criteria->getMaxResults());
    }

    public function testApplyWithFilter()
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', 10, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertSame(10, $criteria->getMaxResults());
    }
}
