<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\RawLayout;
use Oro\Component\Layout\RawLayoutBuilderInterface;
use Oro\Component\Layout\Tests\Unit\Stubs\LayoutContextStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LayoutBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $rawLayoutBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $layoutManipulator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $blockFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $expressionProcessor;

    /** @var LayoutBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutBuilder;

    /** @var LayoutBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutBuilderWithoutCache;

    /** @var BlockViewCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $blockViewCache;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $this->blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->renderer = $this->createMock(LayoutRendererInterface::class);
        $this->expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $this->blockViewCache = $this->createMock(BlockViewCache::class);

        $rendererRegistry = new LayoutRendererRegistry();
        $rendererRegistry->addRenderer('test', $this->renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $this->layoutBuilder = $this->getMockBuilder(LayoutBuilder::class)
            ->setConstructorArgs([
                $this->registry,
                $this->rawLayoutBuilder,
                $this->layoutManipulator,
                $this->blockFactory,
                $rendererRegistry,
                $this->expressionProcessor,
                $this->blockViewCache
            ])
            ->onlyMethods(['createLayout'])
            ->getMock();

        $this->layoutBuilderWithoutCache = $this->getMockBuilder(LayoutBuilder::class)
            ->setConstructorArgs([
                $this->registry,
                $this->rawLayoutBuilder,
                $this->layoutManipulator,
                $this->blockFactory,
                $rendererRegistry,
                $this->expressionProcessor
            ])
            ->onlyMethods(['createLayout'])
            ->getMock();
    }

    public function testAdd()
    {
        $id = 'test_id';
        $parentId = 'test_parent_id';
        $blockType = 'test_block_type';
        $options = ['test' => 'val'];
        $siblingId = 'test_sibling_id';
        $prepend = true;

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
        $id = 'test_id';
        $parentId = 'test_parent_id';
        $siblingId = 'test_sibling_id';
        $prepend = true;

        $this->layoutManipulator->expects($this->once())
            ->method('move')
            ->with($id, $parentId, $siblingId, $prepend);

        $result = $this->layoutBuilder->move($id, $parentId, $siblingId, $prepend);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testAddAlias()
    {
        $alias = 'test_alias';
        $id = 'test_id';

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
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('setOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->setOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testAppendOption()
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('appendOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->appendOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testSubtractOption()
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects($this->once())
            ->method('subtractOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->subtractOption($id, $optionName, $optionValue);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testReplaceOption()
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
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
        $id = 'test_id';
        $optionName = 'test_option_name';

        $this->layoutManipulator->expects($this->once())
            ->method('removeOption')
            ->with($id, $optionName);

        $result = $this->layoutBuilder->removeOption($id, $optionName);
        $this->assertSame($this->layoutBuilder, $result);
    }

    public function testChangeBlockType()
    {
        $id = 'test_id';
        $blockType = 'test_block_type';
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

    public function testGetLayoutWithCache()
    {
        $rootView = $this->createMock(BlockView::class);
        $layout = $this->createMock(Layout::class);

        $context = new LayoutContextStub([
            'expressions_evaluate'          => true,
            'expressions_evaluate_deferred' => true,
        ]);
        $this->registry->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->blockViewCache->expects($this->once())
            ->method('fetch')
            ->with($context)
            ->willReturn($rootView);

        $this->blockViewCache->expects($this->never())
            ->method('save');

        $this->expressionProcessor->expects($this->once())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects($this->once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootView->vars['_formThemes'] = ['TestFormTheme1', 'TestFormTheme2'];
        $rootView->vars['_blockThemes'] = [
            'root_id'    => ['RootTheme1', 'RootTheme2'],
            'test_block' => ['TestTheme1', 'TestTheme2']
        ];

        $this->layoutManipulator->expects($this->exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2'], 'root_id'],
                [['TestTheme1', 'TestTheme2'], 'test_block'],
            ]);

        $this->layoutManipulator->expects($this->once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutManipulator->expects($this->never())
            ->method('applyChanges');

        $this->rawLayoutBuilder->expects($this->never())
            ->method('getRawLayout');

        $this->blockFactory->expects($this->never())
            ->method('createBlockView');

        $this->layoutBuilder->expects($this->once())
            ->method('createLayout')
            ->with($this->identicalTo($rootView))
            ->willReturn($layout);

        $layout->expects($this->exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2']],
                [['TestTheme1', 'TestTheme2'], 'test_block']
            ]);

        $layout->expects($this->once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutBuilder
            ->setBlockTheme(['RootTheme1', 'RootTheme2'])
            ->setBlockTheme(['TestTheme1', 'TestTheme2'], 'test_block')
            ->setFormTheme(['TestFormTheme1', 'TestFormTheme2']);

        $result = $this->layoutBuilder->getLayout($context);

        $this->assertSame($layout, $result);
    }

    public function testGetLayoutWithoutCache()
    {
        $rawLayout = $this->createMock(RawLayout::class);
        $rootView = $this->createMock(BlockView::class);
        $layout = $this->createMock(Layout::class);

        $context = new LayoutContextStub([
            'expressions_evaluate'          => true,
            'expressions_evaluate_deferred' => true,
        ]);
        $this->registry->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->blockViewCache->expects($this->once())
            ->method('fetch')
            ->with($context)
            ->willReturn(null);

        $this->blockViewCache->expects($this->once())
            ->method('save')
            ->with($context, $rootView);

        $this->expressionProcessor->expects($this->once())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects($this->once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootId = 'test_id';
        $rootView->vars['id'] = $rootId;

        $this->layoutManipulator->expects($this->exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2']],
                [['TestTheme1', 'TestTheme2'], 'test_block'],
            ]);

        $this->layoutManipulator->expects($this->once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutManipulator->expects($this->once())
            ->method('applyChanges')
            ->with($this->identicalTo($context), false);

        $this->rawLayoutBuilder->expects($this->once())
            ->method('getRawLayout')
            ->willReturn($rawLayout);

        $this->blockFactory->expects($this->once())
            ->method('createBlockView')
            ->with($this->identicalTo($rawLayout), $this->identicalTo($context))
            ->willReturn($rootView);

        $this->layoutBuilder->expects($this->once())
            ->method('createLayout')
            ->with($this->identicalTo($rootView))
            ->willReturn($layout);

        $rawLayout->expects($this->once())
            ->method('getBlockThemes')
            ->willReturn([
                $rootId      => ['RootTheme1', 'RootTheme2'],
                'test_block' => ['TestTheme1', 'TestTheme2']
            ]);

        $layout->expects($this->exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2']],
                [['TestTheme1', 'TestTheme2'], 'test_block']
            ]);

        $rawLayout->expects($this->once())
            ->method('getFormThemes')
            ->willReturn(['TestFormTheme1', 'TestFormTheme2']);

        $layout->expects($this->once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutBuilder
            ->setBlockTheme(['RootTheme1', 'RootTheme2'])
            ->setBlockTheme(['TestTheme1', 'TestTheme2'], 'test_block')
            ->setFormTheme(['TestFormTheme1', 'TestFormTheme2']);

        $result = $this->layoutBuilder->getLayout($context);

        $this->assertSame($layout, $result);
    }

    public function testGetLayoutWithHiddenBlockViews()
    {
        $hiddenWithChild = new BlockView();
        $hiddenWithChild->children = [
            'hidden_parent_child' => new BlockView()
        ];
        $hiddenWithChild->vars['visible'] = false;

        $hiddenChild = new BlockView();
        $hiddenChild->vars['visible'] = false;

        $visibleWithHiddenChild = new BlockView();
        $visibleWithHiddenChild->children = [
            'hidden_child' => $hiddenChild
        ];

        $rootView = new BlockView();
        $rootView->children = [
            'hidden_with_child'         => $hiddenWithChild,
            'visible_with_hidden_child' => $visibleWithHiddenChild,
        ];

        $rawLayout = $this->createMock(RawLayout::class);
        $layout = $this->createMock(Layout::class);
        $layout->expects($this->once())
            ->method('getView')
            ->willReturn($rootView);

        $context = new LayoutContextStub([
            'expressions_evaluate'          => true,
            'expressions_evaluate_deferred' => true,
        ]);

        $this->registry->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->blockViewCache->expects($this->once())
            ->method('fetch')
            ->with($context)
            ->willReturn($rootView);

        $this->expressionProcessor->expects($this->any())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects($this->once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootView->vars['_blockThemes'] = ['RootTheme1', 'RootTheme2'];
        $rootView->vars['_formThemes'] = ['RootFormTheme1', 'RootFormTheme2'];

        $this->layoutManipulator->expects($this->never())
            ->method('applyChanges');

        $this->rawLayoutBuilder->expects($this->never())
            ->method('getRawLayout');

        $this->layoutBuilder->expects($this->once())
            ->method('createLayout')
            ->with($this->identicalTo($rootView))
            ->willReturn($layout);

        $rawLayout->expects($this->never())
            ->method('getBlockThemes');

        $result = $this->layoutBuilder->getLayout($context);

        $expectedRootView = new BlockView();
        $expectedRootView->vars['bag'] = null;
        $expectedRootView->vars['_blockThemes'] = ['RootTheme1', 'RootTheme2'];
        $expectedRootView->vars['_formThemes'] = ['RootFormTheme1', 'RootFormTheme2'];
        $expectedRootView->children = [
            'visible_with_hidden_child' => new BlockView()
        ];

        $this->assertEquals($expectedRootView, $result->getView());
    }

    public function testGetNotAppliedActions()
    {
        $this->layoutManipulator->expects($this->once())
            ->method('getNotAppliedActions')
            ->willReturn(['action1', 'action2']);

        $this->assertSame(['action1', 'action2'], $this->layoutBuilder->getNotAppliedActions());
    }
}
