<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockTypeRegistry;

class BlockTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockTypeRegistry */
    protected $registry;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $blockTypeFactory;

    protected function setUp()
    {
        $this->blockTypeFactory = $this->getMock('Oro\Component\Layout\BlockTypeFactoryInterface');
        $this->registry    = new BlockTypeRegistry($this->blockTypeFactory);
    }

    public function testGetBlockType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($widgetBlockType));

        $this->assertSame($widgetBlockType, $this->registry->getBlockType('widget'));
        // check that the created block type is cached
        $this->assertSame($widgetBlockType, $this->registry->getBlockType('widget'));
    }

    public function testHasBlockType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $buttonBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->exactly(2))
            ->method('createBlockType')
            ->will(
                $this->returnValueMap(
                    [
                        ['widget', $widgetBlockType],
                        ['button', $buttonBlockType]
                    ]
                )
            );

        $this->assertTrue($this->registry->hasBlockType('widget'));
        $this->assertTrue($this->registry->hasBlockType('button'));
        // check that the created block type is cached
        $this->assertTrue($this->registry->hasBlockType('button'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetTypeWithWrongArgument()
    {
        $this->registry->getBlockType(1);
    }

    public function testHasTypeWithWrongArgument()
    {
        $this->assertFalse($this->registry->hasBlockType(1));
    }
}
