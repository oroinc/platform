<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class SetDefaultSortingTest extends OrmRelatedTestCase
{
    /** @var SetDefaultSorting */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        parent::setUp();
        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new GetListContext($configProvider, $metadataProvider);
        $this->processor = new SetDefaultSorting($this->doctrineHelper);
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
        $this->assertEquals(1, $filters->count());
        $sorterFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sorterFilter->getDataType());
    }

    public function testProcessForMixedRequest()
    {
        $this->context->setRequestType(['rest', 'json_api']);
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $sortFilter = new SortFilter('integer');
        $filters = new FilterCollection();
        $filters->add('sort', $sortFilter);
        $this->context->set('filters', $filters);

        $this->processor->process($this->context);

        $this->assertEquals(1, $filters->count());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }
}
