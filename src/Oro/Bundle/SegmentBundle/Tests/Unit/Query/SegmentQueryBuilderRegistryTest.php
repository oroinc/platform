<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Oro\Bundle\SegmentBundle\Query\QueryBuilderInterface;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;

class SegmentQueryBuilderRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQueryBuilder()
    {
        $queryBuilderRegistry = new SegmentQueryBuilderRegistry();

        $queryBuilder = $this->createMock(QueryBuilderInterface::class);
        $queryBuilderRegistry->addQueryBuilder('test', $queryBuilder);

        $this->assertSame($queryBuilder, $queryBuilderRegistry->getQueryBuilder('test'));
    }

    public function testGetQueryBuilderNull()
    {
        $queryBuilderRegistry = new SegmentQueryBuilderRegistry();

        $this->assertNull($queryBuilderRegistry->getQueryBuilder('test'));
    }
}
