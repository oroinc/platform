<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testGenericMethods(): void
    {
        $context = new Context();

        self::assertFalse($context->has('test'));
        self::assertFalse(isset($context['test']));
        self::assertNull($context->get('test'));
        self::assertNull($context['test']);

        $context->set('test', 'value');
        self::assertTrue($context->has('test'));
        self::assertTrue(isset($context['test']));
        self::assertEquals('value', $context->get('test'));
        self::assertEquals('value', $context['test']);

        $context->remove('test');
        self::assertFalse($context->has('test'));
        self::assertFalse(isset($context['test']));
        self::assertNull($context->get('test'));
        self::assertNull($context['test']);

        $context['test1'] = 'value1';
        self::assertTrue($context->has('test1'));
        self::assertTrue(isset($context['test1']));
        self::assertEquals('value1', $context->get('test1'));
        self::assertEquals('value1', $context['test1']);

        unset($context['test1']);
        self::assertFalse($context->has('test1'));
        self::assertFalse(isset($context['test1']));
        self::assertNull($context->get('test1'));
        self::assertNull($context['test1']);

        $context->set('test', null);
        self::assertTrue($context->has('test'));
        self::assertTrue(isset($context['test']));
        self::assertNull($context->get('test'));
        self::assertNull($context['test']);

        self::assertCount(1, $context);
        self::assertEquals(['test' => null], $context->toArray());

        $context->clear();
        self::assertCount(0, $context);
    }

    public function testAction(): void
    {
        $context = new Context();

        $context->setAction('test');
        self::assertEquals('test', $context->getAction());
        self::assertEquals('test', $context->get(Context::ACTION));
    }

    public function testFirstGroup(): void
    {
        $context = new Context();

        self::assertNull($context->getFirstGroup());

        $context->setFirstGroup('test');
        self::assertEquals('test', $context->getFirstGroup());

        $context->setFirstGroup(null);
        self::assertNull($context->getFirstGroup());
    }

    public function testLastGroup(): void
    {
        $context = new Context();

        self::assertNull($context->getLastGroup());

        $context->setLastGroup('test');
        self::assertEquals('test', $context->getLastGroup());

        $context->setLastGroup(null);
        self::assertNull($context->getLastGroup());
    }

    public function testSkippedGroups(): void
    {
        $context = new Context();

        self::assertFalse($context->hasSkippedGroups());
        self::assertSame([], $context->getSkippedGroups());

        $context->skipGroup('test');
        self::assertTrue($context->hasSkippedGroups());
        self::assertSame(['test'], $context->getSkippedGroups());

        $context->skipGroup('test1');
        self::assertTrue($context->hasSkippedGroups());
        self::assertSame(['test', 'test1'], $context->getSkippedGroups());

        $context->skipGroup('test');
        self::assertTrue($context->hasSkippedGroups());
        self::assertSame(['test', 'test1'], $context->getSkippedGroups());

        $context->undoGroupSkipping('test');
        self::assertTrue($context->hasSkippedGroups());
        self::assertSame(['test1'], $context->getSkippedGroups());

        $context->undoGroupSkipping('test1');
        self::assertFalse($context->hasSkippedGroups());
        self::assertSame([], $context->getSkippedGroups());

        $context->skipGroup('test');
        self::assertTrue($context->hasSkippedGroups());
        $context->resetSkippedGroups();
        self::assertFalse($context->hasSkippedGroups());
        self::assertSame([], $context->getSkippedGroups());
    }

    public function testResult(): void
    {
        $context = new Context();

        self::assertFalse($context->hasResult());
        self::assertNull($context->getResult());

        $context->setResult('test');
        self::assertTrue($context->hasResult());
        self::assertEquals('test', $context->getResult());

        $context->setResult(null);
        self::assertTrue($context->hasResult());
        self::assertNull($context->getResult());

        $context->removeResult();
        self::assertFalse($context->hasResult());
        self::assertNull($context->getResult());
    }

    public function testGetChecksum(): void
    {
        $context = new Context();
        self::assertEquals('', $context->getChecksum());

        $context->setAction('test');
        self::assertEquals('5f806996b5064aae1b0c0a2cc0f90d1550f05af7', $context->getChecksum());

        $context->set('key1', 'val1');
        self::assertEquals('64072bb9a8a46f46ea83b5c3d4bf6aa5ee2cdb8a', $context->getChecksum());

        $context->set('key2', null);
        self::assertEquals('64072bb9a8a46f46ea83b5c3d4bf6aa5ee2cdb8a', $context->getChecksum());

        $context->set('key3', ['key' => 'val']);
        self::assertEquals('b382be496f9cc9ba10e545c41ddd1ca9d9f9fb5a', $context->getChecksum());

        $context->remove('key3');
        self::assertEquals('64072bb9a8a46f46ea83b5c3d4bf6aa5ee2cdb8a', $context->getChecksum());
    }
}
