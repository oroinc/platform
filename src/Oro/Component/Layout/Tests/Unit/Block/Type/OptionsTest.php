<?php

namespace Oro\Component\Layout\Tests\Unit\Block\Type;

use Oro\Component\Layout\Block\Type\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    private Options $options;

    #[\Override]
    protected function setUp(): void
    {
        $this->options = new Options(['value' => 'test']);
    }

    public function testGet(): void
    {
        $this->assertEquals('test', $this->options->get('value'));

        $this->expectException(\OutOfBoundsException::class);
        $this->assertFalse($this->options->get('nameNotExist'));
    }

    public function testOffsetGet(): void
    {
        $this->assertEquals('test', $this->options->offsetGet('value'));
    }

    public function testOffsetSet(): void
    {
        $this->options->offsetSet('attribute', 'bar');
        $this->assertSame(['value' => 'test', 'attribute' => 'bar'], $this->options->toArray());
    }

    public function testOffsetUnset(): void
    {
        $this->options->offsetUnset('value');
        $this->assertSame([], $this->options->toArray());
    }

    public function testOffsetExists(): void
    {
        $this->assertTrue($this->options->offsetExists('value'));
        $this->assertFalse($this->options->offsetExists('attr'));
    }

    public function testGetAll(): void
    {
        $this->assertSame(['value' => 'test'], $this->options->toArray());
    }

    public function testSetMultiple(): void
    {
        $values = ['value' => 'test1', 'value2' => 'test2', 'value3' => 'test3'];
        $this->options->setMultiple($values);
        $this->assertEquals($values, $this->options->toArray());
    }
}
