<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\DataCollector\DataCollectorLayoutNameProviderInterface;
use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Event\LayoutBuildAfterEvent;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\Tests\Unit\Stubs\ContextItemStub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

class LayoutDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    private DataCollectorLayoutNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject $layoutNameProvider;

    private PathProviderInterface|\PHPUnit\Framework\MockObject\MockObject $pathProvider;

    private LayoutDataCollector $dataCollector;

    protected function setUp(): void
    {
        $this->layoutNameProvider = $this->createMock(DataCollectorLayoutNameProviderInterface::class);
        $this->pathProvider = $this->createMock(PathProviderInterface::class);

        $configs = [
            'oro_layout.debug_developer_toolbar' => true,
        ];

        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects(self::any())
            ->method('get')
            ->willReturnCallback(static fn ($code) => $configs[$code]);

        $this->dataCollector = new LayoutDataCollector(
            $this->layoutNameProvider,
            $configManager,
            $this->pathProvider,
            true
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('layout', $this->dataCollector->getName());
    }

    public function testCollect(): void
    {
        $paths = ['default/', 'default/sample_route'];
        $this->pathProvider
            ->expects(self::once())
            ->method('getPaths')
            ->willReturn($paths);

        $context = new LayoutContext([], ['string', 'array', 'ContextItemInterface']);

        $contextItemInterface = new ContextItemStub();
        $contextItems = [
            'string' => 'string',
            'array' => ['array'],
            'ContextItemInterface' => $contextItemInterface,
        ];
        foreach ($contextItems as $name => $item) {
            $context->set($name, $item);
        }
        $contextItems['array'] = json_encode($contextItems['array']);
        $contextItems['ContextItemInterface'] = '(object) ContextItemStub::id:1';

        $contextData = [
            'string' => 'string',
            'array' => [],
            'object' => \stdClass::class,
        ];
        foreach ($contextData as $name => $item) {
            $context->data()->set($name, $item);
        }
        $context->resolve();

        $name = 'Sample Name';
        $this->layoutNameProvider
            ->expects(self::once())
            ->method('getNameByContext')
            ->with($context)
            ->willReturn($name);

        $notAppliedActions = [
            [
                'name' => 'add',
                'args' => [
                    'id' => 'customer_sidebar_request',
                    'parentId' => 'customer_sidebar',
                    'blockType' => 'link',
                    'options' => [
                        'visible' => 'true',
                        'attr' => ['class' => 'btn'],
                    ],
                    'siblingId' => 'customer_sidebar_sign_out',
                    'prepend' => true,
                ],
            ],
            [
                'name' => 'remove',
                'args' => [
                    'id' => 'categories_main_menu',
                ],
            ],
        ];
        $layout = $this->createMock(Layout::class);
        $layout
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $layoutBuilder
            ->expects(self::once())
            ->method('getNotAppliedActions')
            ->willReturn($notAppliedActions);
        $this->dataCollector->onBuildAfter(new LayoutBuildAfterEvent($layout, $layoutBuilder));
        $this->dataCollector->collect($this->createMock(Request::class), $this->createMock(Response::class));

        $hash = $context->getHash();
        $data = $this->dataCollector->getData();
        self::assertArrayHasKey($hash, $data);
        self::assertEquals($contextItems, $data[$hash]['context']['items']);
        self::assertArrayHasKey('views', $data[$hash]);
        self::assertEquals(0, $data[$hash]['count']);
        self::assertEquals(2, $data[$hash]['not_applied_actions_count']);
        self::assertEquals($notAppliedActions, $data[$hash]['not_applied_actions']);
        foreach ($data[$hash]['context']['data'] as $datum) {
            self::assertInstanceOf(Data::class, $datum);
        }
        self::assertEquals($paths, $data[$hash]['paths']);
        self::assertEquals($name, $data[$hash]['name']);
    }

    public function testCollectBuildViews(): void
    {
        $paths = ['default/', 'default/sample_route'];
        $this->pathProvider
            ->expects(self::once())
            ->method('getPaths')
            ->willReturn($paths);

        $options = [
            'root' => [
                'id' => 'root',
                'attr' => [],
                'string' => 'root_string',
                'array' => ['root', 'array'],
                'boolean' => true,
                'object' => new \stdClass(),
                'visible' => true,
                'block_prefixes' => ['root', '_root'],
            ],
            'head' => [
                'id' => 'head',
                'string' => 'head_string',
                'array' => ['head', 'array'],
                'boolean' => false,
                'object' => new \stdClass(),
                'visible' => false,
                'block_prefixes' => ['head', '_head'],
            ],
            'body' => [
                'id' => 'body',
                'string' => 'body_string',
                'array' => ['body', 'array'],
                'boolean' => true,
                'object' => new \stdClass(),
                'visible' => true,
                'block_prefixes' => ['container', '_body'],
            ],
        ];
        $tree = [
            'root' => [
                'head' => [],
                'body' => [
                    'undefined' => [],
                ],
            ],
        ];

        $context = new LayoutContext();
        $context->resolve();

        $name = 'Sample Name';
        $this->layoutNameProvider
            ->expects(self::once())
            ->method('getNameByContext')
            ->with($context)
            ->willReturn($name);

        $rootBlock = new BlockView();
        $rootBlock->vars['id'] = key($tree);
        $blockViews = $this->getBlockViews($rootBlock, current($tree));

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            $block = $this->createMock(BlockInterface::class);
            $block->expects(self::any())
                ->method('getContext')
                ->willReturn($context);
            $block->expects(self::any())
                ->method('getId')
                ->willReturn($blockView->vars['id']);

            $this->dataCollector->collectBlockView($block, $blockView);
        }

        $layout = $this->createMock(Layout::class);
        $layout
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $layoutBuilder
            ->expects(self::once())
            ->method('getNotAppliedActions')
            ->willReturn([]);
        $this->dataCollector->onBuildAfter(new LayoutBuildAfterEvent($layout, $layoutBuilder));

        $this->dataCollector->collect($this->createMock(Request::class), $this->createMock(Response::class));

        $hash = $context->getHash();
        $data = $this->dataCollector->getData();
        self::assertArrayHasKey($hash, $data);
        self::assertEquals('root', $data[$hash]['views']['root']['id']);
        self::assertCount(7, $data[$hash]['views']['root']['view_vars']);
        self::assertEquals(['root', '_root'], $data[$hash]['views']['root']['block_prefixes']);
        self::assertCount(2, $data[$hash]['views']['root']['children']);
        self::assertEquals($paths, $data[$hash]['paths']);
        self::assertEquals($name, $data[$hash]['name']);
    }

    /**
     * @param BlockView $rootBlock
     * @param array $tree
     * @param BlockView[] $blockViews
     *
     * @return BlockView[]
     */
    private function getBlockViews(BlockView $rootBlock, array $tree, ?array &$blockViews = []): array
    {
        $blockViews[] = $rootBlock;

        foreach ($tree as $id => $children) {
            $child = new BlockView($rootBlock);
            $child->vars['id'] = $id;

            $this->getBlockViews($child, $children, $result);

            $rootBlock->children[$id] = $child;
        }

        return $blockViews;
    }

    public function testCollectWhenNoLayouts(): void
    {
        $this->dataCollector->collect($this->createMock(Request::class), $this->createMock(Response::class));

        $data = $this->dataCollector->getData();
        self::assertEmpty($data);
    }
}
