<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class SetDefaultSortingTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var SetDefaultSorting */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDefaultSorting($this->doctrineHelper);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForJSONAPIRequest()
    {
        $this->context->getRequestType()->clear();
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(1, $filters->count());
        $sorterFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sorterFilter->getDataType());
    }

    public function testProcessForMixedRequest()
    {
        $sortFilter = new SortFilter('integer');
        $filters = new FilterCollection();
        $filters->add('sort', $sortFilter);
        $this->context->set('filters', $filters);

        $this->context->getRequestType()->clear();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $this->assertEquals(1, $filters->count());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }
}
