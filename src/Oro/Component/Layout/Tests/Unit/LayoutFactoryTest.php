<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutFactory;

class LayoutFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $rendererRegistry;

    /** @var ExpressionProcessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $expressionProcessor;

    /** @var LayoutFactory */
    protected $layoutFactory;

    protected function setUp()
    {
        $this->registry            = $this->createMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->rendererRegistry    = $this->createMock('Oro\Component\Layout\LayoutRendererRegistryInterface');
        $this->expressionProcessor = $this
            ->getMockBuilder('Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutFactory       = new LayoutFactory(
            $this->registry,
            $this->rendererRegistry,
            $this->expressionProcessor
        );
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
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');

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
        $rawLayoutBuilder = $this->createMock('Oro\Component\Layout\RawLayoutBuilderInterface');

        $this->assertInstanceOf(
            'Oro\Component\Layout\DeferredLayoutManipulatorInterface',
            $this->layoutFactory->createLayoutManipulator($rawLayoutBuilder)
        );
    }

    public function testCreateBlockFactory()
    {
        $layoutManipulator = $this->createMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');

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
