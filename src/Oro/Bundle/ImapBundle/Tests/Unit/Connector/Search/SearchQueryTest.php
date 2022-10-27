<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\Search;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryMatch;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;

class SearchQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider valueProviderForInvalidArguments
     */
    public function testValueInvalidArguments(SearchQuery $value, int $match)
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = $this->createSearchQuery();
        $query->value($value, $match);
    }

    /**
     * @dataProvider itemProviderForInvalidArguments
     */
    public function testItemInvalidArguments(string $name, SearchQuery $value, int $match)
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = $this->createSearchQuery();
        $query->item($name, $value, $match);
    }

    /**
     * @dataProvider isEmptyProvider
     */
    public function testIsEmpty(SearchQuery $query, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $query->isEmpty());
    }

    /**
     * @dataProvider isComplexProvider
     */
    public function testIsComplex(SearchQuery $query, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $query->isComplex());
    }

    public function valueProviderForInvalidArguments(): array
    {
        $complexQuery = $this->createSearchQuery();
        $complexQuery->value('product1');
        $complexQuery->value('product2');

        return [
            'SearchQuery as value + SUBSTRING_MATCH' => [$complexQuery, SearchQueryMatch::SUBSTRING_MATCH],
            'SearchQuery as value + EXACT_MATCH' => [$complexQuery, SearchQueryMatch::EXACT_MATCH],
        ];
    }

    public function itemProviderForInvalidArguments(): array
    {
        $sampleQuery = $this->createSearchQuery();
        $sampleQuery->value('product1');
        $sampleQuery->value('product2');

        return [
            'SearchQuery as value + SUBSTRING_MATCH' => [
                'subject',
                $sampleQuery,
                SearchQueryMatch::SUBSTRING_MATCH
            ],
            'SearchQuery as value + EXACT_MATCH' => [
                'subject',
                $sampleQuery,
                SearchQueryMatch::EXACT_MATCH
            ],
        ];
    }

    public function isEmptyProvider(): array
    {
        $empty = $this->createSearchQuery();
        $emptyWithEmptySubQuery = $this->createSearchQuery();
        $emptyWithEmptySubQuery->value($this->createSearchQuery());
        $nonEmpty = $this->createSearchQuery();
        $nonEmpty->value('val');
        $nonEmptyWithNonEmptySubQuery = $this->createSearchQuery();
        $nonEmptySubQuery = $this->createSearchQuery();
        $nonEmptySubQuery->value('val');
        $nonEmptyWithNonEmptySubQuery->value($nonEmptySubQuery);

        return [
            'empty' => [$empty, true],
            'emptyWithEmptySubQuery' => [$emptyWithEmptySubQuery, true],
            'nonEmpty' => [$nonEmpty, false],
            'nonEmptyWithNonEmptySubQuery' => [$nonEmptyWithNonEmptySubQuery, false],
        ];
    }

    public function isComplexProvider(): array
    {
        $empty = $this->createSearchQuery();
        $emptyWithEmptySubQuery = $this->createSearchQuery();
        $emptyWithEmptySubQuery->value($this->createSearchQuery());

        $simple = $this->createSearchQuery();
        $simple->value('val');

        $complex = $this->createSearchQuery();
        $complex->value('val1');
        $complex->value('val2');

        return [
            'empty' => [$empty, false],
            'emptyWithEmptySubQuery' => [$emptyWithEmptySubQuery, false],
            'simple' => [$simple, false],
            'complex' => [$complex, true],
        ];
    }

    private function createSearchQuery(): SearchQuery
    {
        $searchStringManager = $this->createMock(SearchStringManagerInterface::class);
        $searchStringManager->expects($this->any())
            ->method('isAcceptableItem')
            ->willReturn(true);
        $searchStringManager->expects($this->never())
            ->method('buildSearchString');

        return new SearchQuery($searchStringManager);
    }
}
