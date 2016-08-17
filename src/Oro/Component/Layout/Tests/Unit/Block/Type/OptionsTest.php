<?php

namespace Oro\Tests\Unit\Component\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\Options;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Options
     */
    protected $options;

    protected function setUp()
    {
        $this->options = new Options(['value' => 'test']);
    }

    public function testGet()
    {
        $this->assertEquals('test', $this->options->get('value'));

        $this->setExpectedException('InvalidArgumentException');
        $this->assertFalse($this->options->get('nameNotExist'));
    }

    public function testOffsetGet()
    {
        $this->assertEquals('test', $this->options->offsetGet('value'));
    }

    public function testOffsetSet()
    {
        $this->options->offsetSet('attribute', 'bar');
        $this->assertSame(['value' => 'test', 'attribute' => 'bar'], $this->options->getAll());
    }

    public function testOffsetUnset()
    {
        $this->options->offsetUnset('value');
        $this->assertSame([], $this->options->getAll());
    }

    public function testOffsetExists()
    {
        $this->assertTrue($this->options->offsetExists('value'));
        $this->assertFalse($this->options->offsetExists('attr'));
    }

    public function testHasArgument()
    {
        $this->assertTrue($this->options->hasArgument('value'));
        $this->assertFalse($this->options->hasArgument('nameNotExist'));
    }

    public function testGetAll()
    {
        $this->assertSame(['value' => 'test'], $this->options->getAll());
    }
}
