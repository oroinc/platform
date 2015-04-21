<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutRendererRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutRendererRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new LayoutRendererRegistry();
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The layout renderer named "" was not found.
     */
    public function testGetUndefinedDefaultRenderer()
    {
        $this->registry->getRenderer();
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The layout renderer named "undefined" was not found.
     */
    public function testGetUndefinedRenderer()
    {
        $this->registry->getRenderer('undefined');
    }

    public function testGetRenderer()
    {
        // prepare data
        $renderer1 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $renderer2 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->registry->addRenderer('test1', $renderer1);
        $this->registry->addRenderer('test2', $renderer2);
        $this->registry->setDefaultRenderer('test2');

        // test hasRenderer
        $this->assertTrue($this->registry->hasRenderer('test1'));
        $this->assertTrue($this->registry->hasRenderer('test2'));
        $this->assertFalse($this->registry->hasRenderer('undefined'));

        // test getRenderer
        $this->assertSame($renderer2, $this->registry->getRenderer());
        $this->assertSame($renderer1, $this->registry->getRenderer('test1'));
        $this->assertSame($renderer2, $this->registry->getRenderer('test2'));
    }
}
