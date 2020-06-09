<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutRendererRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutRendererRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->registry = new LayoutRendererRegistry();
    }

    public function testGetUndefinedDefaultRenderer()
    {
        $this->expectException(\Oro\Component\Layout\Exception\LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "" was not found.');

        $this->registry->getRenderer();
    }

    public function testGetUndefinedRenderer()
    {
        $this->expectException(\Oro\Component\Layout\Exception\LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "undefined" was not found.');

        $this->registry->getRenderer('undefined');
    }

    public function testGetRenderer()
    {
        // prepare data
        $renderer1 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        $renderer2 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
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
