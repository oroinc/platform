<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use PHPUnit\Framework\TestCase;

class PageNumberFilterTest extends TestCase
{
    public function testApplyWithoutFilter(): void
    {
        $filter = new PageNumberFilter(DataType::UNSIGNED_INTEGER);
        $criteria = new Criteria();

        $filter->apply($criteria);

        self::assertNull($criteria->getFirstResult());
    }

    public function testApplyWithFilter(): void
    {
        $pageSize = 10;
        $pageNumber = 2;
        $expectedOffset = 10;

        $filter = new PageNumberFilter(DataType::UNSIGNED_INTEGER);
        $filterValue = new FilterValue('path', $pageNumber, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertNull($criteria->getFirstResult());

        $criteria->setMaxResults($pageSize);
        $filter->apply($criteria, $filterValue);

        self::assertSame($expectedOffset, $criteria->getFirstResult());
    }

    public function testApplyWithFirstRecordNumberFilter(): void
    {
        $pageSize = 10;
        $firstRecord = 2;
        $expectedOffset = 1;

        $filter = new PageNumberFilter(DataType::UNSIGNED_INTEGER);
        $filterValue = new FilterValue('path', $firstRecord, null);
        $criteria = new Criteria();

        $filter->useFirstRecordNumber();
        $filter->apply($criteria, $filterValue);

        self::assertNull($criteria->getFirstResult());

        $criteria->setMaxResults($pageSize);
        $filter->apply($criteria, $filterValue);

        self::assertSame($expectedOffset, $criteria->getFirstResult());
    }

    public function testApplyWithFilterAndNullValue(): void
    {
        $filter = new PageNumberFilter(DataType::UNSIGNED_INTEGER);
        $filterValue = new FilterValue('path', null, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertNull($criteria->getFirstResult());
    }

    public function testApplyWithFilterAndValueLessThan1(): void
    {
        $this->expectException(InvalidFilterValueException::class);
        $this->expectExceptionMessage('The value should be greater than or equals to 1.');

        $filter = new PageNumberFilter(DataType::UNSIGNED_INTEGER);
        $filterValue = new FilterValue('path', 0, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);
    }
}
