<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultPaging;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class SetDefaultPagingTest extends GetListProcessorTestCase
{
    /** @var SetDefaultPaging */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getPageNumberFilterName')
            ->willReturn('page[number]');
        $filterNames->expects(self::any())
            ->method('getPageSizeFilterName')
            ->willReturn('page[size]');

        $this->processor = new SetDefaultPaging(
            new FilterNamesRegistry([[$filterNames, null]], new RequestExpressionMatcher())
        );
    }

    public function testProcessWhenQueryIsAlreadyExist()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWithDefaultPaging()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        self::assertEquals(10, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        self::assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        self::assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }

    public function testProcessWhenPageSizeExistsInConfig()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(123);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        self::assertEquals(123, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        self::assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        self::assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }

    public function testProcessWhenPagingIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(-1);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(0, $filters);
    }
}
