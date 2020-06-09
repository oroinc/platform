<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddPageNumberToInfoRecord;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AddPageNumberToInfoRecordTest extends GetListProcessorTestCase
{
    /** @var AddPageNumberToInfoRecord */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getPageNumberFilterName')
            ->willReturn('page[number]');

        $this->processor = new AddPageNumberToInfoRecord(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenPaginationIsNotSupported()
    {
        $this->processor->process($this->context);
        self::assertNull($this->context->getInfoRecords());
    }

    public function testProcessWhenPageNumberFilterValueDoesNotExist()
    {
        $this->context->getFilters()->add('page[number]', new PageNumberFilter(DataType::UNSIGNED_INTEGER));
        $this->processor->process($this->context);
        self::assertSame(
            ['' => ['page_number' => 1]],
            $this->context->getInfoRecords()
        );
    }

    public function testProcessWhenPageNumberFilterValueExists()
    {
        $this->context->getFilters()->add('page[number]', new PageNumberFilter(DataType::UNSIGNED_INTEGER));
        $this->context->getFilterValues()->set('page[number]', new FilterValue('number', 2));
        $this->processor->process($this->context);
        self::assertSame(
            ['' => ['page_number' => 2]],
            $this->context->getInfoRecords()
        );
    }

    public function testProcessWhenInfoRecordForPrimaryCollectionAlreadyExists()
    {
        $this->context->getFilters()->add('page[number]', new PageNumberFilter(DataType::UNSIGNED_INTEGER));
        $this->context->getFilterValues()->set('page[number]', new FilterValue('number', 2));
        $this->context->setInfoRecords(['' => ['key' => 'value']]);
        $this->processor->process($this->context);
        self::assertSame(
            ['' => ['key' => 'value', 'page_number' => 2]],
            $this->context->getInfoRecords()
        );
    }
}
