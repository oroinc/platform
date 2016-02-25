<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\SetDefaultPaging;

class SetDefaultPagingTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetDefaultPaging */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new GetListContext($configProvider, $metadataProvider);
        $this->processor = new SetDefaultPaging();
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setQuery($qb);
        $this->processor->process($this->context);
        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForJSONAPIRequest()
    {
        $this->context->setRequestType(['json_api']);
        $this->processor->process($this->context);
        $filters = $this->context->getFilters();
        $this->assertEquals(2, $filters->count());
        $this->assertEquals(10, $filters->get('page[size]')->getDefaultValue());
        $this->assertEquals(1, $filters->get('page[number]')->getDefaultValue());
        $expectedFiltersOrder = ['page[size]', 'page[number]'];
        $currentIndex = 0;
        foreach ($filters as $filterKey => $filterDefinition) {
            $this->assertEquals($expectedFiltersOrder[$currentIndex], $filterKey);
            $currentIndex++;
        }
    }

    public function testProcessForMixedRequest()
    {
        $this->context->setRequestType(['rest', 'json_api']);
        $pageSizeFilter = new PageSizeFilter('integer');
        $pageNumberFilter = new PageNumberFilter('integer');
        $filters = new FilterCollection();
        $filters->add('limit', $pageSizeFilter);
        $filters->add('page', $pageNumberFilter);
        $this->context->set('filters', $filters);

        $this->processor->process($this->context);

        $this->assertEquals(2, $filters->count());
        $expectedFiltersOrder = ['page[size]', 'page[number]'];
        $currentIndex = 0;
        foreach ($filters as $filterKey => $filterDefinition) {
            $this->assertEquals($expectedFiltersOrder[$currentIndex], $filterKey);
            $currentIndex++;
        }
    }
}
