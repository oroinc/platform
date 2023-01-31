<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContextStack;
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
    protected LayoutRegistryInterface|\PHPUnit\Framework\MockObject\MockObject $registry;

    protected RawLayoutBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $rawLayoutBuilder;

    protected DeferredLayoutManipulatorInterface|\PHPUnit\Framework\MockObject\MockObject $layoutManipulator;

    protected BlockFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $blockFactory;

    protected LayoutRendererInterface|\PHPUnit\Framework\MockObject\MockObject $renderer;

    protected LayoutContextStack $layoutContextStack;

    protected ExpressionProcessor|\PHPUnit\Framework\MockObject\MockObject $expressionProcessor;

    /** @var LayoutBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutBuilder;

    /** @var LayoutBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutBuilderWithoutCache;

    protected BlockViewCache|\PHPUnit\Framework\MockObject\MockObject $blockViewCache;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $this->blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->renderer = $this->createMock(LayoutRendererInterface::class);
        $this->layoutContextStack = new LayoutContextStack();
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
                $this->layoutContextStack,
                $this->blockViewCache,
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
                $this->expressionProcessor,
                $this->layoutContextStack,
            ])
            ->onlyMethods(['createLayout'])
            ->getMock();
    }

    public function testAdd(): void
    {
        $id = 'test_id';
        $parentId = 'test_parent_id';
        $blockType = 'test_block_type';
        $options = ['test' => 'val'];
        $siblingId = 'test_sibling_id';
        $prepend = true;

        $this->layoutManipulator->expects(self::once())
            ->method('add')
            ->with($id, $parentId, $blockType, $options, $siblingId, $prepend);

        $result = $this->layoutBuilder->add($id, $parentId, $blockType, $options, $siblingId, $prepend);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testRemove(): void
    {
        $id = 'test_id';

        $this->layoutManipulator->expects(self::once())
            ->method('remove')
            ->with($id);

        $result = $this->layoutBuilder->remove($id);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testMove(): void
    {
        $id = 'test_id';
        $parentId = 'test_parent_id';
        $siblingId = 'test_sibling_id';
        $prepend = true;

        $this->layoutManipulator->expects(self::once())
            ->method('move')
            ->with($id, $parentId, $siblingId, $prepend);

        $result = $this->layoutBuilder->move($id, $parentId, $siblingId, $prepend);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testAddAlias(): void
    {
        $alias = 'test_alias';
        $id = 'test_id';

        $this->layoutManipulator->expects(self::once())
            ->method('addAlias')
            ->with($alias, $id);

        $result = $this->layoutBuilder->addAlias($alias, $id);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testRemoveAlias(): void
    {
        $alias = 'test_alias';

        $this->layoutManipulator->expects(self::once())
            ->method('removeAlias')
            ->with($alias);

        $result = $this->layoutBuilder->removeAlias($alias);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testSetOption(): void
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects(self::once())
            ->method('setOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->setOption($id, $optionName, $optionValue);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testAppendOption(): void
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects(self::once())
            ->method('appendOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->appendOption($id, $optionName, $optionValue);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testSubtractOption(): void
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $optionValue = 'test_option_value';

        $this->layoutManipulator->expects(self::once())
            ->method('subtractOption')
            ->with($id, $optionName, $optionValue);

        $result = $this->layoutBuilder->subtractOption($id, $optionName, $optionValue);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testReplaceOption(): void
    {
        $id = 'test_alias';
        $optionName = 'test_option_name';
        $oldOptionValue = 'old_option_value';
        $newOptionValue = 'new_option_value';

        $this->layoutManipulator->expects(self::once())
            ->method('replaceOption')
            ->with($id, $optionName, $oldOptionValue, $newOptionValue);

        $result = $this->layoutBuilder->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testRemoveOption(): void
    {
        $id = 'test_id';
        $optionName = 'test_option_name';

        $this->layoutManipulator->expects(self::once())
            ->method('removeOption')
            ->with($id, $optionName);

        $result = $this->layoutBuilder->removeOption($id, $optionName);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testChangeBlockType(): void
    {
        $id = 'test_id';
        $blockType = 'test_block_type';
        $optionsCallback = function (array $options) {
            return $options;
        };

        $this->layoutManipulator->expects(self::once())
            ->method('changeBlockType')
            ->with($id, $blockType, self::identicalTo($optionsCallback));

        $result = $this->layoutBuilder->changeBlockType($id, $blockType, $optionsCallback);
        self::assertSame($this->layoutBuilder, $result);
    }

    public function testClear(): void
    {
        $this->layoutManipulator->expects(self::once())
            ->method('clear');
        $this->rawLayoutBuilder->expects(self::once())
            ->method('clear');

        self::assertSame($this->layoutBuilder, $this->layoutBuilder->clear());
    }

    public function testGetLayoutWithCache(): void
    {
        $rootView = $this->createMock(BlockView::class);
        $layout = $this->createMock(Layout::class);

        $context = new LayoutContextStub([
            'expressions_evaluate' => true,
            'expressions_evaluate_deferred' => true,
        ]);
        $this->registry->expects(self::once())
            ->method('configureContext')
            ->with(self::identicalTo($context));

        $this->blockViewCache->expects(self::once())
            ->method('fetch')
            ->with($context)
            ->willReturn($rootView);

        $this->blockViewCache->expects(self::never())
            ->method('save');

        $this->expressionProcessor->expects(self::once())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects(self::once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootView->vars['_formThemes'] = ['TestFormTheme1', 'TestFormTheme2'];
        $rootView->vars['_blockThemes'] = [
            'root_id' => ['RootTheme1', 'RootTheme2'],
            'test_block' => ['TestTheme1', 'TestTheme2'],
        ];

        $this->layoutManipulator->expects(self::exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2'], 'root_id'],
                [['TestTheme1', 'TestTheme2'], 'test_block'],
            ]);

        $this->layoutManipulator->expects(self::once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutManipulator->expects(self::never())
            ->method('applyChanges');

        $this->rawLayoutBuilder->expects(self::never())
            ->method('getRawLayout');

        $this->blockFactory->expects(self::never())
            ->method('createBlockView');

        $this->layoutBuilder->expects(self::once())
            ->method('createLayout')
            ->with(self::identicalTo($rootView))
            ->willReturn($layout);

        $layout->expects(self::exactly(2))
            ->method('setBlockTheme')
            ->withConsecutive([['RootTheme1', 'RootTheme2'], 'root_id'], [['TestTheme1', 'TestTheme2'], 'test_block'])
            ->willReturnSelf();

        $layout->expects(self::once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2'])
            ->willReturnSelf();

        $this->layoutBuilder
            ->setBlockTheme(['RootTheme1', 'RootTheme2'])
            ->setBlockTheme(['TestTheme1', 'TestTheme2'], 'test_block')
            ->setFormTheme(['TestFormTheme1', 'TestFormTheme2']);

        $result = $this->layoutBuilder->getLayout($context);

        self::assertSame($layout, $result);
    }

    public function testGetLayoutWithoutCache(): void
    {
        $rawLayout = $this->createMock(RawLayout::class);
        $rootView = $this->createMock(BlockView::class);
        $layout = $this->createMock(Layout::class);

        $context = new LayoutContextStub([
            'expressions_evaluate' => true,
            'expressions_evaluate_deferred' => true,
        ]);
        $this->registry->expects(self::once())
            ->method('configureContext')
            ->with(self::identicalTo($context));

        $this->blockViewCache->expects(self::once())
            ->method('fetch')
            ->with($context)
            ->willReturn(null);

        $this->blockViewCache->expects(self::once())
            ->method('save')
            ->with($context, $rootView);

        $this->expressionProcessor->expects(self::once())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects(self::once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootId = 'test_id';
        $rootView->vars['id'] = $rootId;

        $this->layoutManipulator->expects(self::exactly(2))
            ->method('setBlockTheme')
            ->willReturnMap([
                [['RootTheme1', 'RootTheme2']],
                [['TestTheme1', 'TestTheme2'], 'test_block'],
            ]);

        $this->layoutManipulator->expects(self::once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2']);

        $this->layoutManipulator->expects(self::once())
            ->method('applyChanges')
            ->with(self::identicalTo($context), false);

        $this->rawLayoutBuilder->expects(self::once())
            ->method('getRawLayout')
            ->willReturn($rawLayout);

        $this->blockFactory->expects(self::once())
            ->method('createBlockView')
            ->with(self::identicalTo($rawLayout), self::identicalTo($context))
            ->willReturn($rootView);

        $this->layoutBuilder->expects(self::once())
            ->method('createLayout')
            ->with(self::identicalTo($rootView))
            ->willReturn($layout);

        $rawLayout->expects(self::once())
            ->method('getBlockThemes')
            ->willReturn([
                $rootId => ['RootTheme1', 'RootTheme2'],
                'test_block' => ['TestTheme1', 'TestTheme2'],
            ]);

        $layout->expects(self::exactly(2))
            ->method('setBlockTheme')
            ->withConsecutive([['RootTheme1', 'RootTheme2'], 'test_id'], [['TestTheme1', 'TestTheme2'], 'test_block'])
            ->willReturnSelf();

        $rawLayout->expects(self::once())
            ->method('getFormThemes')
            ->willReturn(['TestFormTheme1', 'TestFormTheme2']);

        $layout->expects(self::once())
            ->method('setFormTheme')
            ->with(['TestFormTheme1', 'TestFormTheme2'])
            ->willReturnSelf();

        $this->layoutBuilder
            ->setBlockTheme(['RootTheme1', 'RootTheme2'])
            ->setBlockTheme(['TestTheme1', 'TestTheme2'], 'test_block')
            ->setFormTheme(['TestFormTheme1', 'TestFormTheme2']);

        $result = $this->layoutBuilder->getLayout($context);

        self::assertSame($layout, $result);
    }

    public function testGetLayoutWithHiddenBlockViews(): void
    {
        $hiddenWithChild = new BlockView();
        $hiddenWithChild->children = [
            'hidden_parent_child' => new BlockView(),
        ];
        $hiddenWithChild->vars['visible'] = false;

        $hiddenChild = new BlockView();
        $hiddenChild->vars['visible'] = false;

        $visibleWithHiddenChild = new BlockView();
        $visibleWithHiddenChild->children = [
            'hidden_child' => $hiddenChild,
        ];

        $rootView = new BlockView();
        $rootView->children = [
            'hidden_with_child' => $hiddenWithChild,
            'visible_with_hidden_child' => $visibleWithHiddenChild,
        ];

        $rawLayout = $this->createMock(RawLayout::class);
        $layout = $this->createMock(Layout::class);
        $layout->expects(self::once())
            ->method('getView')
            ->willReturn($rootView);

        $context = new LayoutContextStub([
            'expressions_evaluate' => true,
            'expressions_evaluate_deferred' => true,
        ]);

        $this->registry->expects(self::once())
            ->method('configureContext')
            ->with(self::identicalTo($context));

        $this->blockViewCache->expects(self::once())
            ->method('fetch')
            ->with($context)
            ->willReturn($rootView);

        $this->expressionProcessor->expects(self::any())
            ->method('processExpressions');

        $optionValueBag = $this->createMock(OptionValueBag::class);
        $optionValueBag->expects(self::once())
            ->method('buildValue');

        $rootView->vars['bag'] = $optionValueBag;
        $rootView->vars['_blockThemes'] = ['RootTheme1', 'RootTheme2'];
        $rootView->vars['_formThemes'] = ['RootFormTheme1', 'RootFormTheme2'];

        $this->layoutManipulator->expects(self::never())
            ->method('applyChanges');

        $this->rawLayoutBuilder->expects(self::never())
            ->method('getRawLayout');

        $this->layoutBuilder->expects(self::once())
            ->method('createLayout')
            ->with(self::identicalTo($rootView))
            ->willReturn($layout);

        $rawLayout->expects(self::never())
            ->method('getBlockThemes');

        $result = $this->layoutBuilder->getLayout($context);

        $expectedRootView = new BlockView();
        $expectedRootView->vars['bag'] = null;
        $expectedRootView->vars['_blockThemes'] = ['RootTheme1', 'RootTheme2'];
        $expectedRootView->vars['_formThemes'] = ['RootFormTheme1', 'RootFormTheme2'];
        $expectedRootView->children = [
            'visible_with_hidden_child' => new BlockView(),
        ];

        self::assertEquals($expectedRootView, $result->getView());
    }

    public function testGetNotAppliedActions(): void
    {
        $this->layoutManipulator->expects(self::once())
            ->method('getNotAppliedActions')
            ->willReturn(['action1', 'action2']);

        self::assertSame(['action1', 'action2'], $this->layoutBuilder->getNotAppliedActions());
    }
}
