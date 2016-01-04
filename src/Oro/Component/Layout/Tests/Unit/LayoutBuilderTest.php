<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $rawLayoutBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutManipulator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $blockFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var LayoutBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->registry          = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->rawLayoutBuilder  = $this->getMock('Oro\Component\Layout\RawLayoutBuilderInterface');
        $this->layoutManipulator = $this->getMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');
        $this->blockFactory      = $this->getMock('Oro\Component\Layout\BlockFactoryInterface');
        $this->renderer          = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');

        $rendererRegistry = new LayoutRendererRegistry();
        $rendererRegistry->addRenderer('test', $this->renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $this->layoutBuilder = $this->getMockBuilder('Oro\Component\Layout\LayoutBuilder')
            ->setConstructorArgs(
                [
                    $this->registry,
                    $this->rawLayoutBuilder,
                    $this->layoutManipulator,
                    $this->blockFactory,
                    $rendererRegistry
                ]
            )
            ->setMethods(['createLayout'])
            ->getMock();
    }

    public function testAdd()
    {
        $id        = 'test_id';
        $parentId  = 'test_parent_id';
        $blockType = 'test_block_type';
        $options   = ['test' => 'val'];
        $siblingId = 'test_sibling_id';
        $prepend   = true;

        $this->layoutManipulator->expects($this->once())
            ->method('add')
            ->with($id, $parentId, $blockType, $options, $siblingId, $prepend);

        $result = $this->layoutBuilder->add($id, $parentId, $blockType, $options, $siblingId, $prepend);
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

    public function testAppendOption()
    {
        $id          = 'test_alias';
        $optionName  = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('appendOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->appendOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testSubtractOption()
    {
        $id          = 'test_alias';
        $optionName  = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('subtractOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->subtractOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testReplaceOption()
    {
        $id             = 'test_alias';
        $optionName     = 'test_option_name';
        $oldOptionValue = 'old_option_value';
        $newOptionValue = 'new_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('replaceOption')
            ->with($id, $optionName, $oldOptionValue, $newOptionValue);

        $result = $this->layoutBuilder->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);
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

    public function testChangeBlockType()
    {
        $id              = 'test_id';
        $blockType       = 'test_block_type';
        $optionsCallback = function (array $options) {
            return $options;
        };

        $this->layoutManipulator->expects($this->once())
            ->method('changeBlockType')
            ->with($id, $blockType, $this->identicalTo($optionsCallback));

        $result = $this->layoutBuilder->changeBlockType($id, $blockType, $optionsCallback);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testClear()
    {
        $this->layoutManipulator->expects($this->once())
            ->method('clear');
        $this->rawLayoutBuilder->expects($this->once())
            ->method('clear');

        $this->assertSame($this->layoutBuilder, $this->layoutBuilder->clear());
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

        $context->expects($this->once())
            ->method('isResolved')
            ->will($this->returnValue(false));
        $context->expects($this->once())
            ->method('resolve');
        $this->registry->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->layoutManipulator->expects($this->at(0))
            ->method('setBlockTheme')
            ->with('RootTheme1', $this->identicalTo(null));
        $this->layoutManipulator->expects($this->at(1))
            ->method('setBlockTheme')
            ->with(['RootTheme2', 'RootTheme3'], $this->identicalTo(null));
        $this->layoutManipulator->expects($this->at(2))
            ->method('setBlockTheme')
            ->with(['TestTheme1', 'TestTheme2'], 'test_block');
        $this->layoutManipulator->expects($this->at(3))
            ->method('setBlockTheme')
            ->with('TestTheme3', 'test_block');

        $this->layoutManipulator->expects($this->at(4))
            ->method('setFormTheme')
            ->with('TestFormTheme1');
        $this->layoutManipulator->expects($this->at(5))
            ->method('setFormTheme')
            ->with(['TestFormTheme2']);

        $this->layoutManipulator->expects($this->once())
            ->method('applyChanges')
            ->with($this->identicalTo($context), false);
        $this->rawLayoutBuilder->expects($this->once())
            ->method('getRawLayout')
            ->will($this->returnValue($rawLayout));
        $this->blockFactory->expects($this->once())
            ->method('createBlockView')
            ->with($this->identicalTo($rawLayout), $this->identicalTo($context), $rootId)
            ->will($this->returnValue($rootView));
        $this->layoutBuilder->expects($this->once())
            ->method('createLayout')
            ->with($this->identicalTo($rootView))
            ->will($this->returnValue($layout));

        $rawLayout->expects($this->once())->method('getRootId')
            ->will($this->returnValue($rootId));
        $rawLayout->expects($this->once())
            ->method('getBlockThemes')
            ->will(
                $this->returnValue(
                    [
                        $rootId => ['RootTheme1', 'RootTheme2', 'RootTheme3'],
                        'test_block' => ['TestTheme1', 'TestTheme2', 'TestTheme3']
                    ]
                )
            );
        $layout->expects($this->at(0))
            ->method('setBlockTheme')
            ->with(['RootTheme1', 'RootTheme2', 'RootTheme3'], $this->identicalTo(null));
        $layout->expects($this->at(1))
            ->method('setBlockTheme')
            ->with(['TestTheme1', 'TestTheme2', 'TestTheme3'], 'test_block');
        $layout->expects($this->exactly(2))
            ->method('setBlockTheme');

        $rawLayout->expects($this->once())->method('getFormThemes')
            ->will($this->returnValue(['TestFormTheme1', 'TestFormTheme2']));
        $layout->expects($this->at(2))
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);
        $layout->expects($this->once())
            ->method('setFormTheme');

        $this->layoutBuilder
            ->setBlockTheme('RootTheme1')
            ->setBlockTheme(['RootTheme2', 'RootTheme3'])
            ->setBlockTheme(['TestTheme1', 'TestTheme2'], 'test_block')
            ->setBlockTheme('TestTheme3', 'test_block')
            ->setFormTheme('TestFormTheme1')
            ->setFormTheme(['TestFormTheme2']);

        $result = $this->layoutBuilder->getLayout($context, $rootId);
        $this->assertSame($layout, $result);
    }
}
