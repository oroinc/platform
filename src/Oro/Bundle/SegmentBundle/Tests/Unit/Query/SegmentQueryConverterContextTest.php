<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterContext;
use PHPUnit\Framework\TestCase;

class SegmentQueryConverterContextTest extends TestCase
{
    private SegmentQueryConverterContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new SegmentQueryConverterContext();
    }

    public function testReset(): void
    {
        $initialContext = clone $this->context;

        $this->context->setAliasPrefix('test_prefix');

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testAliasPrefix(): void
    {
        self::assertNull($this->context->getAliasPrefix());

        $prefix = 'test_prefix';
        $this->context->setAliasPrefix($prefix);
        self::assertSame($prefix, $this->context->getAliasPrefix());
    }

    public function testGenerateTableAliasWhenNoAliasPrefix(): void
    {
        self::assertEquals('t1', $this->context->generateTableAlias());
        self::assertEquals('t2', $this->context->generateTableAlias());
    }

    public function testGenerateTableAliasWithAliasPrefix(): void
    {
        $this->context->setAliasPrefix('test_prefix');

        self::assertEquals('t1_test_prefix', $this->context->generateTableAlias());
        self::assertEquals('t2_test_prefix', $this->context->generateTableAlias());
    }
}
