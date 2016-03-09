<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class SortFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExpression()
    {
        $filter      = new SortFilter(DataType::ORDER_BY);
        $filterValue = new FilterValue('path', 'value', 'operator');

        $this->assertNull($filter->createExpression(null));
        $this->assertNull($filter->createExpression($filterValue));
    }

    public function testApplyWithoutFilter()
    {
        $filter   = new SortFilter(DataType::ORDER_BY);
        $criteria = new Criteria();

        $filter->apply($criteria);

        $this->assertEmpty($criteria->getOrderings());
    }

    public function testApplyWithFilter()
    {
        $orderingValue = ['id' => 'DESC', 'name' => 'ASC'];

        $filter        = new SortFilter(DataType::ORDER_BY);
        $filterValue   = new FilterValue('path', $orderingValue, null);
        $criteria      = new Criteria();

        $filter->apply($criteria, $filterValue);

        $this->assertNotEmpty($criteria->getOrderings());
        $this->assertSame($orderingValue, $criteria->getOrderings());
    }
}
