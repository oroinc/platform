<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datasource;

use Oro\Bundle\SearchBundle\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class YamlToSearchQueryConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchQueryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    public function setUp()
    {
        $this->query = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testSelectFromInConverter()
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
            ->method('from')
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

    public function testWhereInConverter()
    {
        $config = [
            'query' => [
                'where' => [
                    'and' => ['product.id <> :parent'],
                    'or' => ['product.id <> :parent2']
                ]
            ]
        ];

        $this->query->expects($this->at(0))
            ->method('setWhere')
            ->with('product.id <> :parent');

        $this->query->expects($this->at(1))
            ->method('setWhere')
            ->with('product.id <> :parent2', AbstractSearchQuery::WHERE_OR);

        $testable = new YamlToSearchQueryConverter();
        $testable->process($this->query, $config);
    }
}
