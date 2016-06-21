<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;

class FilterCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  FilterCollection */
    protected $filterCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filterCollection = new FilterCollection();
    }

    public function testActions()
    {
        $this->assertCount(0, $this->filterCollection);
        $this->assertTrue($this->filterCollection->isEmpty());

        $sortFilter     = new SortFilter('type');
        $pageSizeFilter = new PageSizeFilter('type');
        $includeFilter  = new IncludeFilter('type');

        $this->filterCollection->add('filter1', $sortFilter);
        $this->filterCollection->add('filter2', $pageSizeFilter);

        $this->assertFalse($this->filterCollection->isEmpty());

        $this->assertTrue($this->filterCollection->has('filter1'));
        $this->assertTrue($this->filterCollection->has('filter2'));
        $this->assertFalse($this->filterCollection->has('filter3'));

        $this->assertCount(2, $this->filterCollection);
        $this->assertEquals(2, $this->filterCollection->count());

        $this->assertSame($sortFilter, $this->filterCollection->get('filter1'));
        $this->filterCollection->set('filter1', $includeFilter);
        $this->assertSame($includeFilter, $this->filterCollection->get('filter1'));

        $this->filterCollection->remove('filter2');
        $this->assertNull($this->filterCollection->get('filter2'));

        $this->assertEquals(new \ArrayIterator(['filter1' => $includeFilter]), $this->filterCollection->getIterator());

        $this->filterCollection->offsetSet('[filter][sort]', $sortFilter);
        $this->filterCollection->offsetSet('[filter][page]', $pageSizeFilter);

        $this->assertCount(3, $this->filterCollection);
        $this->assertSame($sortFilter, $this->filterCollection->offsetGet('[filter][sort]'));
        $this->assertTrue($this->filterCollection->offsetExists('[filter][page]'));

        $this->filterCollection->offsetUnset('[filter][sort]');
        $this->assertFalse($this->filterCollection->offsetExists('[filter][sort]'));
    }
}
