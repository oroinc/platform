<?php

namespace Oro\Component\Block\Tests\Unit;

use Oro\Component\Block\BlockRegistry;

class BlockRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockRegistry */
    protected $blockRegistry;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $blockTypeFactory;

    protected function setUp()
    {
        $this->blockTypeFactory = $this->getMock('Oro\Component\Block\BlockTypeFactoryInterface');
        $this->blockRegistry    = new BlockRegistry($this->blockTypeFactory);
    }

    public function testGetType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Block\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($widgetBlockType));

        $this->assertSame($widgetBlockType, $this->blockRegistry->getType('widget'));
        // check that the created block type is cached
        $this->assertSame($widgetBlockType, $this->blockRegistry->getType('widget'));
    }

    public function testHasType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Block\BlockTypeInterface');
        $buttonBlockType = $this->getMock('Oro\Component\Block\BlockTypeInterface');

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

        $this->assertTrue($this->blockRegistry->hasType('widget'));
        $this->assertTrue($this->blockRegistry->hasType('button'));
        // check that the created block type is cached
        $this->assertTrue($this->blockRegistry->hasType('button'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetTypeWithWrongArgument()
    {
        $this->blockRegistry->getType(1);
    }

    public function testHasTypeWithWrongArgument()
    {
        $this->assertFalse($this->blockRegistry->hasType(1));
    }
}
