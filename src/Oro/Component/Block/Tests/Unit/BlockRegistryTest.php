<?php

namespace Oro\Component\Block\Tests\Unit;

use Oro\Component\Block\BlockRegistry;

class BlockRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockRegistry */
    protected $blockRegistry;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $blockTypeFactory;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $blockType;

    protected function setUp()
    {
        $this->blockTypeFactory = $this->getMock('Oro\Component\Block\BlockTypeFactoryInterface');
        $this->blockType = $this->getMock('Oro\Component\Block\BlockTypeInterface');
        $this->blockRegistry = new BlockRegistry($this->blockTypeFactory);
    }

    public function testGetType()
    {
        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->will($this->returnValue($this->blockType));
        $this->blockRegistry->getType('widget');
        $this->blockRegistry->getType('widget');
    }

    public function testHasType()
    {
        $this->blockTypeFactory->expects($this->exactly(2))
            ->method('createBlockType')
            ->will($this->returnValue($this->blockType));
        $this->blockRegistry->hasType('widget');
        $this->blockRegistry->hasType('button');
        $this->blockRegistry->hasType('widget');
    }
}
