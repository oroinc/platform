<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Processor\NormalizeSearchAggregatedData;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeSearchAggregatedDataTest extends GetListProcessorTestCase
{
    /** @var ValueTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueTransformer;

    /** @var NormalizeSearchAggregatedData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueTransformer = $this->createMock(ValueTransformer::class);

        $this->processor = new NormalizeSearchAggregatedData($this->valueTransformer);
    }

    public function testProcessWhenNoInfoRecords(): void
    {
        $this->processor->process($this->context);
        self::assertNull($this->context->getInfoRecords());
    }

    public function testProcessWhenNoAggregatedData(): void
    {
        $this->context->setInfoRecords([]);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getInfoRecords());
    }

    public function testProcessWithEmptyAggregatedData(): void
    {
        $infoRecords = ['aggregatedData' => []];
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    public function testProcessWhenNoAggregateFilter(): void
    {
        $infoRecords = ['aggregatedData' => ['field1' => 100]];
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    public function testProcessForNotSupportedFilter(): void
    {
        $infoRecords = ['aggregatedData' => ['field1' => 100]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->getFilters()->add('aggregations', $this->createMock(StandaloneFilter::class));
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    /**
     * @dataProvider dateTimeValueDataProvider
     */
    public function testProcessForDateTimeValue(
        mixed $value,
        string $valueToNormalize,
        string $normalizedValue
    ): void {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_DATETIME]);

        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with(new \DateTime($valueToNormalize), DataType::DATETIME, $this->context->getNormalizationContext())
            ->willReturn($normalizedValue);

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => $value]]);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => $normalizedValue]],
            $this->context->getInfoRecords()
        );
    }

    /**
     * @dataProvider dateTimeValueDataProvider
     */
    public function testProcessForDateTimeValueForCountAggregation(
        mixed $value,
        string $valueToNormalize,
        string $normalizedValue
    ): void {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_DATETIME]);

        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with(new \DateTime($valueToNormalize), DataType::DATETIME, $this->context->getNormalizationContext())
            ->willReturn($normalizedValue);

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => ['item1' => ['value' => $value]]]]);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => ['item1' => ['value' => $normalizedValue]]]],
            $this->context->getInfoRecords()
        );
    }

    public function dateTimeValueDataProvider(): array
    {
        return [
            [100, '@100', '2020-01-01\T00:00:00\Z'],
            [100.1, '@100.1', '2020-01-01\T00:00:00\Z'],
            ['100', '@100', '2020-01-01\T00:00:00\Z'],
            ['100.1', '@100.1', '2020-01-01\T00:00:00\Z'],
            ['2020-01-01 12:12:12', '2020-01-01 12:12:12', '2020-01-01\T00:00:00\Z'],
        ];
    }

    public function testProcessForNullDateTimeValue(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_DATETIME]);

        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => null]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    public function testProcessForNullDateTimeValueForCountAggregation(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_DATETIME]);

        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => ['item1' => ['value' => null]]]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    public function testProcessForNotDateTimeValue(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_INTEGER]);

        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => 100]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }

    public function testProcessForNotDateTimeValueForCountAggregation(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn(['field1' => SearchQuery::TYPE_INTEGER]);

        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => ['item1' => ['value' => 100]]]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
    }
}
