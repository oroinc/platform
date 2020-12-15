<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultPaging;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SetDefaultPagingTest extends GetListProcessorTestCase
{
    private const DEFAULT_PAGE_SIZE = 10;

    /** @var SetDefaultPaging */
    private $processor;

    protected function setUp(): void
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
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            self::DEFAULT_PAGE_SIZE
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
        self::assertSame(self::DEFAULT_PAGE_SIZE, $pageSizeFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[size]'));
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        self::assertSame(1, $pageNumberFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[number]'));

        // check that filters are added in correct order
        self::assertEquals(['page[size]', 'page[number]'], array_keys($filters->all()));
    }

    public function testProcessWhenPagingFiltersAlreadyExist()
    {
        $customDefaultPageSize = 5;
        $this->context->getFilters()->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, '', 1),
            false
        );
        $this->context->getFilters()->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, '', $customDefaultPageSize),
            false
        );

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('page[size]');
        self::assertSame($customDefaultPageSize, $pageSizeFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[size]'));
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        self::assertSame(1, $pageNumberFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[number]'));

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
        self::assertSame(123, $pageSizeFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[size]'));
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page[number]');
        self::assertSame(1, $pageNumberFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('page[number]'));

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
