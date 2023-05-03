<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutRendererRegistryTest extends \PHPUnit\Framework\TestCase
{
    private LayoutRendererRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new LayoutRendererRegistry();
    }

    public function testGetUndefinedDefaultRenderer()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "" was not found.');

        $this->registry->getRenderer();
    }

    public function testGetUndefinedRenderer()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The layout renderer named "undefined" was not found.');

        $this->registry->getRenderer('undefined');
    }

    public function testGetRenderer()
    {
        // prepare data
        $renderer1 = $this->createMock(LayoutRendererInterface::class);
        $renderer2 = $this->createMock(LayoutRendererInterface::class);
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
