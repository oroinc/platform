<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\EntitySerializer\AssociationQuery;

class AssociationQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateForCollectionQuery(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $targetEntityClass = 'Test\Entity';

        $associationQuery = new AssociationQuery($qb, $targetEntityClass, true);
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertEquals($targetEntityClass, $associationQuery->getTargetEntityClass());
        self::assertTrue($associationQuery->isCollection());
    }

    public function testCreateForNotCollectionQuery(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $targetEntityClass = 'Test\Entity';

        $associationQuery = new AssociationQuery($qb, $targetEntityClass, false);
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertEquals($targetEntityClass, $associationQuery->getTargetEntityClass());
        self::assertFalse($associationQuery->isCollection());
    }

    public function testCreateWithoutCollectionArgument(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $targetEntityClass = 'Test\Entity';

        $associationQuery = new AssociationQuery($qb, $targetEntityClass);
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertEquals($targetEntityClass, $associationQuery->getTargetEntityClass());
        self::assertTrue($associationQuery->isCollection());
    }
}
