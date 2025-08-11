<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
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

    #[\Override]
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
        self::assertFalse($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessWhenNoAggregatedData(): void
    {
        $this->context->setInfoRecords([]);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getInfoRecords());
        self::assertFalse($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessWithEmptyAggregatedData(): void
    {
        $infoRecords = ['aggregatedData' => []];
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
        self::assertFalse($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessWhenNoAggregateDataTypes(): void
    {
        $infoRecords = ['aggregatedData' => ['field1' => 100]];
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    /**
     * @dataProvider dateTimeValueDataProvider
     */
    public function testProcessForDateTimeValue(
        mixed $value,
        string $valueToNormalize,
        string $normalizedValue
    ): void {
        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with(new \DateTime($valueToNormalize), DataType::DATETIME, $this->context->getNormalizationContext())
            ->willReturn($normalizedValue);

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => $value]]);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_DATETIME]
        );
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => $normalizedValue]],
            $this->context->getInfoRecords()
        );
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    /**
     * @dataProvider dateTimeValueDataProvider
     */
    public function testProcessForDateTimeValueForCountAggregation(
        mixed $value,
        string $valueToNormalize,
        string $normalizedValue
    ): void {
        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with(new \DateTime($valueToNormalize), DataType::DATETIME, $this->context->getNormalizationContext())
            ->willReturn($normalizedValue);

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => [$value => 1]]]);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_DATETIME]
        );
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => [['value' => $normalizedValue, 'count' => 1]]]],
            $this->context->getInfoRecords()
        );
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function dateTimeValueDataProvider(): array
    {
        return [
            [100, '@100', '2020-01-01\T00:00:00\Z'],
            ['100', '@100', '2020-01-01\T00:00:00\Z'],
            ['100.1', '@100.1', '2020-01-01\T00:00:00\Z'],
            ['2020-01-01 12:12:12', '2020-01-01 12:12:12', '2020-01-01\T00:00:00\Z'],
        ];
    }

    public function testProcessForNullDateTimeValue(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => null]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_DATETIME]
        );
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessForNullDateTimeValueForCountAggregation(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => [null => 1]]]);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_DATETIME]
        );
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => [['value' => null, 'count' => 1]]]],
            $this->context->getInfoRecords()
        );
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessForNotDateTimeValue(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => 100]];
        $this->context->setInfoRecords($infoRecords);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_INTEGER]
        );
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessForNotDateTimeValueForCountAggregation(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $this->context->setInfoRecords(['aggregatedData' => ['field1' => ['item1' => 100]]]);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_INTEGER]
        );
        $this->processor->process($this->context);
        self::assertSame(
            ['aggregatedData' => ['field1' => [['value' => 'item1', 'count' => 100]]]],
            $this->context->getInfoRecords()
        );
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }

    public function testProcessWhenAggregatedDataAreAlreadyNormalized(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        $infoRecords = ['aggregatedData' => ['field1' => '2020-01-01\T00:00:00\Z']];
        $this->context->setProcessed(NormalizeSearchAggregatedData::OPERATION_NAME);
        $this->context->setInfoRecords($infoRecords);
        $this->context->set(
            NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES,
            ['field1' => SearchQuery::TYPE_DATETIME]
        );
        $this->processor->process($this->context);
        self::assertSame($infoRecords, $this->context->getInfoRecords());
        self::assertTrue($this->context->isProcessed(NormalizeSearchAggregatedData::OPERATION_NAME));
    }
}
