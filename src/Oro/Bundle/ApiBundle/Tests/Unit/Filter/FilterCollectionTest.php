<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;

class FilterCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var  FilterCollection */
    private $filterCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filterCollection = new FilterCollection();
    }

    public function testActions()
    {
        self::assertCount(0, $this->filterCollection);
        self::assertTrue($this->filterCollection->isEmpty());

        $sortFilter = new SortFilter('type');
        $pageSizeFilter = new PageSizeFilter('type');
        $includeFilter = new IncludeFilter('type');

        $this->filterCollection->add('filter1', $sortFilter);
        $this->filterCollection->add('filter2', $pageSizeFilter);

        self::assertFalse($this->filterCollection->isEmpty());

        self::assertTrue($this->filterCollection->has('filter1'));
        self::assertTrue($this->filterCollection->has('filter2'));
        self::assertFalse($this->filterCollection->has('filter3'));

        self::assertCount(2, $this->filterCollection);
        self::assertEquals(2, $this->filterCollection->count());

        self::assertSame($sortFilter, $this->filterCollection->get('filter1'));
        $this->filterCollection->set('filter1', $includeFilter);
        self::assertSame($includeFilter, $this->filterCollection->get('filter1'));

        $this->filterCollection->remove('filter2');
        self::assertNull($this->filterCollection->get('filter2'));

        self::assertEquals(new \ArrayIterator(['filter1' => $includeFilter]), $this->filterCollection->getIterator());

        $this->filterCollection->offsetSet('[filter][sort]', $sortFilter);
        $this->filterCollection->offsetSet('[filter][page]', $pageSizeFilter);

        self::assertCount(3, $this->filterCollection);
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

        $this->filterCollection->add('filter1', $filter1);
        $this->filterCollection->add('filter[filter2]', $filter2);

        self::assertTrue($this->filterCollection->has('filter1'));
        self::assertFalse($this->filterCollection->has('filter[filter1]'));
        self::assertTrue($this->filterCollection->has('filter2'));
        self::assertTrue($this->filterCollection->has('filter[filter2]'));

        self::assertSame($filter1, $this->filterCollection->get('filter1'));
        self::assertNull($this->filterCollection->get('filter[filter1]'));
        self::assertSame($filter2, $this->filterCollection->get('filter2'));
        self::assertSame($filter2, $this->filterCollection->get('filter[filter2]'));
    }
}
