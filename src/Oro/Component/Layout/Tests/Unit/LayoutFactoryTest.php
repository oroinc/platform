<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutFactory;

class LayoutFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extensionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $rendererRegistry;

    /** @var LayoutFactory */
    protected $layoutFactory;

    protected function setUp()
    {
        $this->extensionManager = $this->getMock('Oro\Component\Layout\ExtensionManagerInterface');
        $this->rendererRegistry = $this->getMock('Oro\Component\Layout\LayoutRendererRegistryInterface');
        $this->layoutFactory    = new LayoutFactory($this->extensionManager, $this->rendererRegistry);
    }

    public function testGetExtensionManager()
    {
        $this->assertSame($this->extensionManager, $this->layoutFactory->getExtensionManager());
    }

    public function testGetRendererRegistry()
    {
        $this->assertSame($this->rendererRegistry, $this->layoutFactory->getRendererRegistry());
    }

    public function testGetBlockType()
    {
        $name = 'test';
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->extensionManager->expects($this->once())
            ->method('getBlockType')
            ->with($name)
            ->will($this->returnValue($type));

        $this->assertSame($type, $this->layoutFactory->getBlockType($name));
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
