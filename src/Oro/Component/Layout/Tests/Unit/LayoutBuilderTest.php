<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutBuilder;

class LayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $rawLayoutBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutManipulator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutViewFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutFactory;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->rawLayoutBuilder  = $this->getMock('Oro\Component\Layout\RawLayoutBuilderInterface');
        $this->layoutManipulator = $this->getMock('Oro\Component\Layout\DeferredRawLayoutManipulatorInterface');
        $this->layoutViewFactory = $this->getMock('Oro\Component\Layout\LayoutViewFactoryInterface');
        $this->layoutFactory     = $this->getMock('Oro\Component\Layout\LayoutFactoryInterface');

        $this->layoutBuilder = new LayoutBuilder(
            $this->rawLayoutBuilder,
            $this->layoutManipulator,
            $this->layoutViewFactory,
            $this->layoutFactory
        );
    }

    public function testAdd()
    {
        $id        = 'test_id';
        $parentId  = 'test_parent_id';
        $blockType = 'test_block_type';
        $options   = ['test' => 'val'];

        $this->layoutManipulator->expects($this->once())
            ->method('add')
            ->with($id, $parentId, $blockType, $options);

        $result = $this->layoutBuilder->add($id, $parentId, $blockType, $options);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testRemove()
    {
        $id = 'test_id';

        $this->layoutManipulator->expects($this->once())
            ->method('remove')
            ->with($id);

        $result = $this->layoutBuilder->remove($id);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testMove()
    {
        $id        = 'test_id';
        $parentId  = 'test_parent_id';
        $siblingId = 'test_sibling_id';
        $prepend   = true;

        $this->layoutManipulator->expects($this->once())
            ->method('move')
            ->with($id, $parentId, $siblingId, $prepend);

        $result = $this->layoutBuilder->move($id, $parentId, $siblingId, $prepend);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testAddAlias()
    {
        $alias = 'test_alias';
        $id    = 'test_id';

        $this->layoutManipulator->expects($this->once())
            ->method('addAlias')
            ->with($alias, $id);

        $result = $this->layoutBuilder->addAlias($alias, $id);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testRemoveAlias()
    {
        $alias = 'test_alias';

        $this->layoutManipulator->expects($this->once())
            ->method('removeAlias')
            ->with($alias);

        $result = $this->layoutBuilder->removeAlias($alias);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testSetOption()
    {
        $id          = 'test_alias';
        $optionName  = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('setOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->setOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testRemoveOption()
    {
        $id         = 'test_id';
        $optionName = 'test_option_name';

        $this->layoutManipulator->expects($this->once())
            ->method('removeOption')
            ->with($id, $optionName);

        $result = $this->layoutBuilder->removeOption($id, $optionName);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testGetLayout()
    {
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $rootId  = 'test_id';

        $rawLayout = $this->getMockBuilder('Oro\Component\Layout\RawLayout')
            ->disableOriginalConstructor()
            ->getMock();
        $rootView  = $this->getMockBuilder('Oro\Component\Layout\BlockView')
            ->disableOriginalConstructor()
            ->getMock();
        $layout    = $this->getMockBuilder('Oro\Component\Layout\Layout')
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutManipulator->expects($this->once())
            ->method('applyChanges');
        $this->rawLayoutBuilder->expects($this->once())
            ->method('getRawLayout')
            ->will($this->returnValue($rawLayout));
        $this->layoutViewFactory->expects($this->once())
            ->method('createView')
            ->with($this->identicalTo($rawLayout), $this->identicalTo($context), $rootId)
            ->will($this->returnValue($rootView));
        $this->layoutFactory->expects($this->once())
            ->method('createLayout')
            ->with($this->identicalTo($rootView))
            ->will($this->returnValue($layout));

        $result = $this->layoutBuilder->getLayout($context, $rootId);
        $this->assertSame($layout, $result);
    }
}
