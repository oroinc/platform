<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use PHPUnit\Framework\TestCase;

class SortFilterTest extends TestCase
{
    public function testApplyWithoutFilter(): void
    {
        $filter = new SortFilter(DataType::ORDER_BY);
        $criteria = new Criteria();

        $filter->apply($criteria);

        self::assertEmpty($criteria->getOrderings());
    }

    public function testApplyWithFilter(): void
    {
        $orderingValue = ['id' => 'DESC', 'name' => 'ASC'];

        $filter = new SortFilter(DataType::ORDER_BY);
        $filterValue = new FilterValue('path', $orderingValue, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertNotEmpty($criteria->getOrderings());
        self::assertSame($orderingValue, $criteria->getOrderings());
    }
}
