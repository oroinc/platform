<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;

class FilterCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterCollection */
    private $filterCollection;

    protected function setUp(): void
    {
        $this->filterCollection = new FilterCollection();
    }

    public function testActions()
    {
        self::assertCount(0, $this->filterCollection);
        self::assertTrue($this->filterCollection->isEmpty());

        $filter = new ComparisonFilter('string');
        $sortFilter = new SortFilter('type');
        $pageSizeFilter = new PageSizeFilter('type');
        $newSortFilter = new SortFilter('new_type');

        $this->filterCollection->add('filter1', $filter);
        $this->filterCollection->add('sort', $sortFilter, false);
        $this->filterCollection->add('page[size]', $pageSizeFilter, false);

        self::assertFalse($this->filterCollection->isEmpty());

        self::assertTrue($this->filterCollection->has('filter1'));
        self::assertTrue($this->filterCollection->has('sort'));
        self::assertTrue($this->filterCollection->has('page[size]'));
        self::assertFalse($this->filterCollection->has('filter3'));

        self::assertTrue($this->filterCollection->isIncludeInDefaultGroup('filter1'));
        self::assertFalse($this->filterCollection->isIncludeInDefaultGroup('sort'));
        self::assertFalse($this->filterCollection->isIncludeInDefaultGroup('page[size]'));
        self::assertTrue($this->filterCollection->isIncludeInDefaultGroup('filter3'));

        self::assertCount(3, $this->filterCollection);
        self::assertEquals(3, $this->filterCollection->count());

        self::assertSame($sortFilter, $this->filterCollection->get('sort'));
        $this->filterCollection->set('sort', $newSortFilter);
        self::assertSame($newSortFilter, $this->filterCollection->get('sort'));

        $this->filterCollection->remove('page[size]');
        self::assertNull($this->filterCollection->get('page[size]'));

        self::assertEquals(
            new \ArrayIterator(['filter1' => $filter, 'sort' => $newSortFilter]),
            $this->filterCollection->getIterator()
        );

        $this->filterCollection->offsetSet('[filter][sort]', $sortFilter);
        $this->filterCollection->offsetSet('[filter][page]', $pageSizeFilter);

        self::assertCount(4, $this->filterCollection);
        self::assertSame($sortFilter, $this->filterCollection->offsetGet('[filter][sort]'));
        self::assertTrue($this->filterCollection->offsetExists('[filter][page]'));

        $this->filterCollection->offsetUnset('[filter][sort]');
        self::assertFalse($this->filterCollection->offsetExists('[filter][sort]'));
    }

    public function testDefaultGroup()
    {
        self::assertNull($this->filterCollection->getDefaultGroupName());

        $this->filterCollection->setDefaultGroupName('filter');
        self::assertEquals('filter', $this->filterCollection->getDefaultGroupName());

        $filter1 = new ComparisonFilter('string');
        $filter2 = new ComparisonFilter('string');
        $filter3 = new ComparisonFilter('string');
        $filter4 = new ComparisonFilter('string');

        $this->filterCollection->add('filter1', $filter1);
        $this->filterCollection->add('filter[filter2]', $filter2);
        $this->filterCollection->add('filter3', $filter3, false);
        $this->filterCollection->add('filter[filter4]', $filter4, false);

        self::assertTrue($this->filterCollection->has('filter1'));
        self::assertFalse($this->filterCollection->has('filter[filter1]'));
        self::assertTrue($this->filterCollection->has('filter2'));
        self::assertTrue($this->filterCollection->has('filter[filter2]'));
        self::assertTrue($this->filterCollection->has('filter3'));
        self::assertFalse($this->filterCollection->has('filter[filter3]'));
        self::assertTrue($this->filterCollection->has('filter4'));
        self::assertTrue($this->filterCollection->has('filter[filter4]'));

        self::assertSame($filter1, $this->filterCollection->get('filter1'));
        self::assertNull($this->filterCollection->get('filter[filter1]'));
        self::assertSame($filter2, $this->filterCollection->get('filter2'));
        self::assertSame($filter2, $this->filterCollection->get('filter[filter2]'));
        self::assertSame($filter3, $this->filterCollection->get('filter3'));
        self::assertNull($this->filterCollection->get('filter[filter3]'));
        self::assertSame($filter4, $this->filterCollection->get('filter4'));
        self::assertSame($filter4, $this->filterCollection->get('filter[filter4]'));
    }
}
