<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactory;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class QueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $indexer = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()->getMock();

        $query = $this->getMock(Query::class);

        $indexer->expects($this->once())
            ->method('select')
            ->willReturn($query);

        $config = [];

        $factory = new QueryFactory($indexer);
        $result  = $factory->create($config);

        $this->assertInstanceOf(IndexerQuery::class, $result);
    }
}
