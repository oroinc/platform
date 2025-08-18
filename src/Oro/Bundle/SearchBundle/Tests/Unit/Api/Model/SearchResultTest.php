<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Model;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Model\SearchQueryExecutor;
use Oro\Bundle\SearchBundle\Api\Model\SearchResult;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchResultTest extends TestCase
{
    private SearchQueryInterface&MockObject $query;

    #[\Override]
    protected function setUp(): void
    {
        $this->query = $this->createMock(SearchQueryInterface::class);
    }

    private function getSearchResult(bool $hasMore = false): SearchResult
    {
        return new SearchResult($this->query, new SearchQueryExecutor(), $hasMore);
    }

    public function testGetRecords(): void
    {
        $searchResult = $this->getSearchResult();
        $records = [new Item()];
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::exactly(2))
            ->method('getElements')
            ->willReturn($records);

        self::assertSame($records, $searchResult->getRecords());
        // test that the query is executed only once
        self::assertSame($records, $searchResult->getRecords());
    }

    public function testGetRecordsWhenHasMoreRequestedAndHasMoreRecords(): void
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Item('Test\Entity', '1'),
            new Item('Test\Entity', '2'),
            new Item('Test\Entity', '3')
        ];
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getMaxResults')
            ->willReturn(2);
        $this->query->expects(self::once())
            ->method('setMaxResults')
            ->willReturn(3);
        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::once())
            ->method('getElements')
            ->willReturn($records);

        $expectedRecords = [
            0   => $records[0],
            1   => $records[1],
            '_' => ['has_more' => true]
        ];
        self::assertSame($expectedRecords, $searchResult->getRecords());
    }

    public function testGetRecordsWhenHasMoreRequestedAndNoMoreRecords(): void
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Item('Test\Entity', '1'),
            new Item('Test\Entity', '2')
        ];
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getMaxResults')
            ->willReturn(2);
        $this->query->expects(self::once())
            ->method('setMaxResults')
            ->willReturn(3);
        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::once())
            ->method('getElements')
            ->willReturn($records);

        self::assertSame($records, $searchResult->getRecords());
    }

    public function testGetRecordsWhenHasMoreRequestedButMaxResultsIsNotSet(): void
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Item('Test\Entity', '1'),
            new Item('Test\Entity', '2')
        ];
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getMaxResults')
            ->willReturn(null);
        $this->query->expects(self::never())
            ->method('setMaxResults');
        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::once())
            ->method('getElements')
            ->willReturn($records);

        self::assertSame($records, $searchResult->getRecords());
    }

    public function testGetRecordsForInvalidQuery(): void
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getRecords();
    }

    public function testGetRecordsForInvalidQueryAndLazyResult(): void
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::once())
            ->method('getElements')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getRecords();
    }

    public function testGetRecordsForUnexpectedException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new \InvalidArgumentException('some error'));

        $searchResult->getRecords();
    }

    public function testGetRecordsCount(): void
    {
        $searchResult = $this->getSearchResult();
        $recordsCount = 123;
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::exactly(2))
            ->method('getRecordsCount')
            ->willReturn($recordsCount);

        self::assertSame($recordsCount, $searchResult->getRecordsCount());
        // test that the query is executed only once
        self::assertSame($recordsCount, $searchResult->getRecordsCount());
    }

    public function testGetRecordsCountForInvalidQuery(): void
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getRecordsCount();
    }

    public function testGetRecordsCountForUnexpectedException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new \InvalidArgumentException('some error'));

        $searchResult->getRecordsCount();
    }

    public function testGetAggregatedData(): void
    {
        $searchResult = $this->getSearchResult();
        $aggregatedData = [
            'fieldSum'   => 123,
            'fieldCount' => [
                'val1' => 1,
                'val2' => 10
            ]
        ];
        $result = $this->createMock(Result::class);

        $this->query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);
        $result->expects(self::exactly(2))
            ->method('getAggregatedData')
            ->willReturn($aggregatedData);

        self::assertSame($aggregatedData, $searchResult->getAggregatedData());
        // test that the query is executed only once
        self::assertSame($aggregatedData, $searchResult->getAggregatedData());
    }

    public function testGetAggregatedDataForInvalidQuery(): void
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getAggregatedData();
    }

    public function testGetAggregatedDataForUnexpectedException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new \InvalidArgumentException('some error'));

        $searchResult->getAggregatedData();
    }
}
