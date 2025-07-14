<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverterContext;
use PHPUnit\Framework\TestCase;

class QueryBuilderGroupingOrmQueryConverterContextTest extends TestCase
{
    private QueryBuilderGroupingOrmQueryConverterContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new QueryBuilderGroupingOrmQueryConverterContext();
    }

    public function testReset(): void
    {
        $initialContext = clone $this->context;

        $this->context->setQueryBuilder($this->createMock(QueryBuilder::class));
        $this->context->setRootEntityAlias('test_alias');

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testQueryBuilder(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $this->context->setQueryBuilder($qb);
        self::assertSame($qb, $this->context->getQueryBuilder());
    }

    public function testGetQueryBuilderWhenItWasNotSet(): void
    {
        $this->expectException(\TypeError::class);
        $this->context->getQueryBuilder();
    }

    public function testRootEntityAlias(): void
    {
        self::assertNull($this->context->getRootEntityAlias());

        $alias = 'test_alias';
        $this->context->setRootEntityAlias($alias);
        self::assertSame($alias, $this->context->getRootEntityAlias());
    }
}
