<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverterContext;

class QueryBuilderGroupingOrmQueryConverterContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryBuilderGroupingOrmQueryConverterContext */
    private $context;

    protected function setUp(): void
    {
        $this->context = new QueryBuilderGroupingOrmQueryConverterContext();
    }

    public function testReset()
    {
        $initialContext = clone $this->context;

        $this->context->setQueryBuilder($this->createMock(QueryBuilder::class));
        $this->context->setRootEntityAlias('test_alias');

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testQueryBuilder()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $this->context->setQueryBuilder($qb);
        self::assertSame($qb, $this->context->getQueryBuilder());
    }

    public function testGetQueryBuilderWhenItWasNotSet()
    {
        $this->expectException(\TypeError::class);
        $this->context->getQueryBuilder();
    }

    public function testRootEntityAlias()
    {
        self::assertNull($this->context->getRootEntityAlias());

        $alias = 'test_alias';
        $this->context->setRootEntityAlias($alias);
        self::assertSame($alias, $this->context->getRootEntityAlias());
    }
}
