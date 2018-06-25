<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Datasource;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class YamlToSearchQueryConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $query;

    public function setUp()
    {
        $this->query = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()->getMock();
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

        $this->query->expects($this->at(0))
            ->method('setFrom')
            ->with('product');
        $this->query->expects($this->at(1))
            ->method('addSelect')
            ->with('text.sku');
        $this->query->expects($this->at(2))
            ->method('addSelect')
            ->with('text.name');

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

        $this->query->expects($this->at(0))
            ->method('addWhere')
            ->with(
                new Comparison('id', '!=', 'parent'),
                AbstractSearchQuery::WHERE_AND
            );

        $this->query->expects($this->at(1))
            ->method('addWhere')
            ->with(
                new Comparison('name', '=', 'test'),
                AbstractSearchQuery::WHERE_OR
            );

        $testable = new YamlToSearchQueryConverter();
        $testable->process($this->query, $config);
    }
}
