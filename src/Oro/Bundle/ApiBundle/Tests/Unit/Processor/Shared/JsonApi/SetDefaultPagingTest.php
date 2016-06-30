<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetDefaultPaging;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetDefaultPagingTest extends GetListProcessorTestCase
{
    /** @var SetDefaultPaging */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDefaultPaging();
    }

    public function testProcessWhenQueryIsAlreadyExist()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWithDefaultPaging()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        $this->assertEquals(10, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        $this->assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        $this->assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }

    public function testProcessWhenPageSizeExistsInConfig()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(123);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        $this->assertEquals(123, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        $this->assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        $this->assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }

    public function testProcessWhenPagingIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(-1);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(0, $filters);
    }
}
