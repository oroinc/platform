<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutFactory;

class LayoutFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $rendererRegistry;

    /** @var LayoutFactory */
    protected $layoutFactory;

    protected function setUp()
    {
        $this->registry         = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->rendererRegistry = $this->getMock('Oro\Component\Layout\LayoutRendererRegistryInterface');
        $this->layoutFactory    = new LayoutFactory($this->registry, $this->rendererRegistry);
    }

    public function testGetRegistry()
    {
        $this->assertSame($this->registry, $this->layoutFactory->getRegistry());
    }

    public function testGetRendererRegistry()
    {
        $this->assertSame($this->rendererRegistry, $this->layoutFactory->getRendererRegistry());
    }

    public function testGetType()
    {
        $name = 'test';
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->registry->expects($this->once())
            ->method('getType')
            ->with($name)
            ->will($this->returnValue($type));

        $this->assertSame($type, $this->layoutFactory->getType($name));
    }

    public function testCreateRawLayoutBuilder()
    {
        $this->assertInstanceOf(
            'Oro\Component\Layout\RawLayoutBuilderInterface',
            $this->layoutFactory->createRawLayoutBuilder()
        );
    }

    public function testCreateLayoutManipulator()
    {
        $rawLayoutBuilder = $this->getMock('Oro\Component\Layout\RawLayoutBuilderInterface');

        $this->assertInstanceOf(
            'Oro\Component\Layout\DeferredLayoutManipulatorInterface',
            $this->layoutFactory->createLayoutManipulator($rawLayoutBuilder)
        );
    }

    public function testCreateBlockFactory()
    {
        $layoutManipulator = $this->getMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');

        $this->assertInstanceOf(
            'Oro\Component\Layout\BlockFactoryInterface',
            $this->layoutFactory->createBlockFactory($layoutManipulator)
        );
    }

    public function testCreateLayoutBuilder()
    {
        $this->assertInstanceOf(
            'Oro\Component\Layout\LayoutBuilderInterface',
            $this->layoutFactory->createLayoutBuilder()
        );
    }
}
