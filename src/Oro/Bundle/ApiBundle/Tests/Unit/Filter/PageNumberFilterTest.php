<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class PageNumberFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExpression()
    {
        $filter = new PageNumberFilter(DataType::INTEGER);

        $this->assertNull($filter->createExpression(null));
        $this->assertNull($filter->createExpression(new FilterValue('path', 'value', 'operator')));
    }

    public function testApplyWithoutFilter()
    {
        $filter   = new PageNumberFilter(DataType::INTEGER);
        $criteria = new Criteria();

        $filter->apply($criteria);

        $this->assertNull($criteria->getFirstResult());
    }

    public function testApplyWithFilter()
    {
        $pageSize       = 10;
        $pageNum        = 2;
        $expectedOffset = 10;

        $filter      = new PageNumberFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', $pageNum, null);
        $criteria    = new Criteria();

        $filter->apply($criteria, $filterValue);

        $this->assertNull($criteria->getFirstResult());

        $criteria->setMaxResults($pageSize);
        $filter->apply($criteria, $filterValue);

        $this->assertSame($expectedOffset, $criteria->getFirstResult());
    }
}
