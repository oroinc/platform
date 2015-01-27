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
        $this->registry         = new BlockTypeRegistry($this->blockTypeFactory);
    }

    public function testGetBlockType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($widgetBlockType));
        $widgetBlockType->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('widget'));

        $this->assertSame($widgetBlockType, $this->registry->getBlockType('widget'));
        // check that the created block type is cached
        $this->assertSame($widgetBlockType, $this->registry->getBlockType('widget'));
    }

    public function testHasBlockType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $widgetBlockType->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('widget'));
        $buttonBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $buttonBlockType->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('button'));

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
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type name must not be empty.
     */
    public function testGetTypeWithEmptyName($name)
    {
        $this->registry->getBlockType($name);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testHasTypeWithEmptyName($name)
    {
        $this->assertFalse($this->registry->hasBlockType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testGetTypeWithNotStringName()
    {
        $this->registry->getBlockType(1);
    }

    public function testHasTypeWithNotStringName()
    {
        $this->assertFalse($this->registry->hasBlockType(1));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type name does not match the name declared in the class implementing this type. Expected "widget", given "button".
     */
    // @codingStandardsIgnoreEnd
    public function testGetTypeWhenGivenNameDoesNotMatchNameDeclaredInClass()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue($widgetBlockType));
        $widgetBlockType->expects($this->exactly(2))
            ->method('getName')
            ->will($this->returnValue('button'));

        $this->registry->getBlockType('widget');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type named "widget" was not found.
     */
    public function testGetTypeUndefined()
    {
        $this->blockTypeFactory->expects($this->once())
            ->method('createBlockType')
            ->with('widget')
            ->will($this->returnValue(null));

        $this->registry->getBlockType('widget');
    }

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }
}
