<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Oro\Bundle\SegmentBundle\Query\QueryBuilderInterface;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;
use PHPUnit\Framework\TestCase;

class SegmentQueryBuilderRegistryTest extends TestCase
{
    public function testGetQueryBuilder(): void
    {
        $queryBuilderRegistry = new SegmentQueryBuilderRegistry();

        $queryBuilder = $this->createMock(QueryBuilderInterface::class);
        $queryBuilderRegistry->addQueryBuilder('test', $queryBuilder);

        self::assertSame($queryBuilder, $queryBuilderRegistry->getQueryBuilder('test'));
    }

    public function testGetQueryBuilderNull(): void
    {
        $queryBuilderRegistry = new SegmentQueryBuilderRegistry();

        self::assertNull($queryBuilderRegistry->getQueryBuilder('test'));
    }
}
