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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchResultTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchQueryInterface */
    private $query;

    protected function setUp(): void
    {
        $this->query = $this->createMock(SearchQueryInterface::class);
    }

    private function getSearchResult(bool $hasMore = false): SearchResult
    {
        return new SearchResult($this->query, new SearchQueryExecutor(), $hasMore);
    }

    public function testGetRecords()
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

    public function testGetRecordsWhenHasMoreRequestedAndHasMoreRecords()
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Result\Item('Test\Entity', '1'),
            new Result\Item('Test\Entity', '2'),
            new Result\Item('Test\Entity', '3')
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

    public function testGetRecordsWhenHasMoreRequestedAndNoMoreRecords()
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Result\Item('Test\Entity', '1'),
            new Result\Item('Test\Entity', '2')
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

    public function testGetRecordsWhenHasMoreRequestedButMaxResultsIsNotSet()
    {
        $searchResult = $this->getSearchResult(true);
        $records = [
            new Result\Item('Test\Entity', '1'),
            new Result\Item('Test\Entity', '2')
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

    public function testGetRecordsForInvalidQuery()
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getRecords();
    }

    public function testGetRecordsForInvalidQueryAndLazyResult()
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

    public function testGetRecordsForUnexpectedException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new \InvalidArgumentException('some error'));

        $searchResult->getRecords();
    }

    public function testGetRecordsCount()
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

    public function testGetRecordsCountForInvalidQuery()
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getRecordsCount();
    }

    public function testGetRecordsCountForUnexpectedException()
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

    public function testGetAggregatedDataForInvalidQuery()
    {
        $this->expectException(InvalidSearchQueryException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new DriverException('some error', $this->createMock(PDOException::class)));

        $searchResult->getAggregatedData();
    }

    public function testGetAggregatedDataForUnexpectedException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $searchResult = $this->getSearchResult();

        $this->query->expects(self::once())
            ->method('getResult')
            ->willThrowException(new \InvalidArgumentException('some error'));

        $searchResult->getAggregatedData();
    }
}
