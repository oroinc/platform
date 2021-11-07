<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Item;

use Oro\Bundle\BatchBundle\Item\ExecutionContext;

class ExecutionContextTest extends \PHPUnit\Framework\TestCase
{
    private ExecutionContext $executionContext;

    protected function setUp(): void
    {
        $this->executionContext = new ExecutionContext();
    }

    public function testIsDirty(): void
    {
        self::assertFalse($this->executionContext->isDirty());
        $this->executionContext->put('test_key', 'test_value');
        self::assertTrue($this->executionContext->isDirty());
    }

    public function testClearDirtyFlag(): void
    {
        $this->executionContext->put('test_key', 'test_value');
        self::assertTrue($this->executionContext->isDirty());
        $this->executionContext->clearDirtyFlag();
        self::assertFalse($this->executionContext->isDirty());
    }

    public function testPut(): void
    {
        $this->executionContext->put('test_key', 'test_value');
        self::assertEquals('test_value', $this->executionContext->get('test_key'));
    }

    public function testGet(): void
    {
        self::assertNull($this->executionContext->get('test_key'));
        $this->executionContext->put('test_key', 'test_value');
        self::assertEquals('test_value', $this->executionContext->get('test_key'));
    }

    public function testRemove(): void
    {
        self::assertNull($this->executionContext->get('test_key'));
        $this->executionContext->put('test_key', 'test_value');
        self::assertEquals('test_value', $this->executionContext->get('test_key'));
        $this->executionContext->remove('test_key');
        self::assertNull($this->executionContext->get('test_key'));
    }

    public function testGetKeys(): void
    {
        self::assertEmpty($this->executionContext->getKeys());
        $this->executionContext->put('test_key1', 'test_value1');
        $this->executionContext->put('test_key2', 'test_value2');
        $this->executionContext->put('test_key3', 'test_value3');
        $expectedKeys = ['test_key1', 'test_key2', 'test_key3'];

        self::assertEquals($expectedKeys, $this->executionContext->getKeys());
    }
}
