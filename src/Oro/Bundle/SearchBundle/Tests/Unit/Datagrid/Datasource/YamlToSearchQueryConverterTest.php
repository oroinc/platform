<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Datasource;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class YamlToSearchQueryConverterTest extends TestCase
{
    private SearchQueryInterface&MockObject $query;

    #[\Override]
    protected function setUp(): void
    {
        $this->query = $this->createMock(SearchQueryInterface::class);
    }

    public function testProcessSelectFrom(): void
    {
        $config = [
            'query' => [
                'select' => [
                    'text.sku',
                    'text.name'
                ],
                'from' => ['product']
            ]
        ];

        $this->query->expects($this->once())
            ->method('setFrom')
            ->with('product');
        $this->query->expects($this->exactly(2))
            ->method('addSelect')
            ->withConsecutive(
                ['text.sku'],
                ['text.name']
            );

        $testable = new YamlToSearchQueryConverter();
        $testable->process($this->query, $config);
    }

    public function testProcessWhere(): void
    {
        $config = [
            'query' => [
                'where' => [
                    'and' => ['id != parent'],
                    'or' => ['name = test']
                ]
            ]
        ];

        $this->query->expects($this->exactly(2))
            ->method('addWhere')
            ->withConsecutive(
                [new Comparison('id', '!=', 'parent'), AbstractSearchQuery::WHERE_AND],
                [new Comparison('name', '=', 'test'), AbstractSearchQuery::WHERE_OR]
            );

        $testable = new YamlToSearchQueryConverter();
        $testable->process($this->query, $config);
    }

    public function testProcessHints(): void
    {
        $config = [
            'query' => [
                'where' => [
                    'and' => ['id != parent'],
                    'or' => ['name = test']
                ]
            ],
            'hints' => [
                'HINT_SEARCH_TYPE',
                ['name' => 'HINT_SEARCH_TERM', 'value' => 'test']
            ]
        ];

        $this->query->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [Query::HINT_SEARCH_TYPE, true],
                [Query::HINT_SEARCH_TERM, 'test']
            );

        $testable = new YamlToSearchQueryConverter();
        $testable->process($this->query, $config);
    }
}
