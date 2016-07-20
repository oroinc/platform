<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testGenericMethods()
    {
        $context = new Context();

        $this->assertFalse($context->has('test'));
        $this->assertFalse(isset($context['test']));
        $this->assertNull($context->get('test'));
        $this->assertNull($context['test']);

        $context->set('test', 'value');
        $this->assertTrue($context->has('test'));
        $this->assertTrue(isset($context['test']));
        $this->assertEquals('value', $context->get('test'));
        $this->assertEquals('value', $context['test']);

        $context->remove('test');
        $this->assertFalse($context->has('test'));
        $this->assertFalse(isset($context['test']));
        $this->assertNull($context->get('test'));
        $this->assertNull($context['test']);

        $context['test1'] = 'value1';
        $this->assertTrue($context->has('test1'));
        $this->assertTrue(isset($context['test1']));
        $this->assertEquals('value1', $context->get('test1'));
        $this->assertEquals('value1', $context['test1']);

        unset($context['test1']);
        $this->assertFalse($context->has('test1'));
        $this->assertFalse(isset($context['test1']));
        $this->assertNull($context->get('test1'));
        $this->assertNull($context['test1']);

        $context->set('test', null);
        $this->assertTrue($context->has('test'));
        $this->assertTrue(isset($context['test']));
        $this->assertNull($context->get('test'));
        $this->assertNull($context['test']);

        $this->assertEquals(1, count($context));
        $this->assertEquals(['test' => null], $context->toArray());

        $context->clear();
        $this->assertEquals(0, count($context));
    }

    public function testAction()
    {
        $context = new Context();

        $this->assertNull($context->getAction());

        $context->setAction('test');
        $this->assertEquals('test', $context->getAction());
        $this->assertEquals('test', $context->get(Context::ACTION));
    }

    public function testFirstGroup()
    {
        $context = new Context();

        $this->assertNull($context->getFirstGroup());

        $context->setFirstGroup('test');
        $this->assertEquals('test', $context->getFirstGroup());
    }

    public function testLastGroup()
    {
        $context = new Context();

        $this->assertNull($context->getLastGroup());

        $context->setLastGroup('test');
        $this->assertEquals('test', $context->getLastGroup());
    }

    public function testSkippedGroups()
    {
        $context = new Context();

        $this->assertFalse($context->hasSkippedGroups());
        $this->assertSame([], $context->getSkippedGroups());

        $context->skipGroup('test');
        $this->assertTrue($context->hasSkippedGroups());
        $this->assertEquals(['test'], $context->getSkippedGroups());

        $context->skipGroup('test1');
        $this->assertTrue($context->hasSkippedGroups());
        $this->assertEquals(['test', 'test1'], $context->getSkippedGroups());

        $context->skipGroup('test');
        $this->assertTrue($context->hasSkippedGroups());
        $this->assertEquals(['test', 'test1'], $context->getSkippedGroups());

        $context->undoGroupSkipping('test');
        $this->assertTrue($context->hasSkippedGroups());
        $this->assertEquals(['test1'], $context->getSkippedGroups());

        $context->undoGroupSkipping('test1');
        $this->assertFalse($context->hasSkippedGroups());
        $this->assertSame([], $context->getSkippedGroups());
    }

    public function testResult()
    {
        $context = new Context();

        $this->assertFalse($context->hasResult());
        $this->assertNull($context->getResult());

        $context->setResult('test');
        $this->assertTrue($context->hasResult());
        $this->assertEquals('test', $context->getResult());
        $this->assertEquals('test', $context->get(Context::RESULT));

        $context->setResult(null);
        $this->assertTrue($context->hasResult());

        $context->removeResult();
        $this->assertFalse($context->hasResult());
    }
}
