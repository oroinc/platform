<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Datasource;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class YamlToSearchQueryConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    protected function setUp(): void
    {
        $this->query = $this->createMock(SearchQueryInterface::class);
    }

    public function testProcessSelectFrom()
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

    public function testProcessWhere()
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

    public function testProcessHints()
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
