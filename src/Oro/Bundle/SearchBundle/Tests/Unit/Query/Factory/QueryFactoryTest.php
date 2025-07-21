<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactory;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use PHPUnit\Framework\TestCase;

class QueryFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $indexer = $this->createMock(Indexer::class);

        $query = $this->createMock(Query::class);

        $indexer->expects($this->once())
            ->method('select')
            ->willReturn($query);

        $config = [];

        $factory = new QueryFactory($indexer);
        $result = $factory->create($config);

        $this->assertInstanceOf(IndexerQuery::class, $result);
    }
}
