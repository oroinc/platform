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
        $this->blockRegistry = new BlockRegistry($this->blockTypeFactory);
    }

    public function testGetType()
    {
        $blockWidget = $this->getMock('Oro\Component\Block\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($blockWidget));

        $this->assertSame($blockWidget, $this->blockRegistry->getType('widget'));
        $this->assertSame($blockWidget, $this->blockRegistry->getType('widget'));
    }

    public function testHasType()
    {
        $blockWidget = $this->getMock('Oro\Component\Block\BlockTypeInterface');
        $buttonWidget = $this->getMock('Oro\Component\Block\BlockTypeInterface');
        $map = [
            ['widget', $blockWidget],
            ['button', $buttonWidget]
        ];

        $this->blockTypeFactory->expects($this->exactly(2))
            ->method('createBlockType')
            ->will($this->returnValueMap($map));

        $this->assertEquals(true, $this->blockRegistry->hasType('widget'));
        $this->assertEquals(true, $this->blockRegistry->hasType('button'));
        $this->assertEquals(true, $this->blockRegistry->hasType('button'));
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
        $this->assertEquals(false, $this->blockRegistry->hasType(1));
    }
}
