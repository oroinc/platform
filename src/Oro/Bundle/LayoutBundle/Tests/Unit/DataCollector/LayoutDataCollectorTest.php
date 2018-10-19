<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\Tests\Unit\Stubs\ContextItemStub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LayoutDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutContextHolder|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextHolder;

    /** @var LayoutDataCollector */
    protected $dataCollector;

    protected function setUp()
    {
        $this->contextHolder = $this->createMock(LayoutContextHolder::class);

        $configs = [
            'oro_layout.debug_developer_toolbar' => true
        ];

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($code) use ($configs) {
                return $configs[$code];
            }));

        $this->dataCollector = new LayoutDataCollector($this->contextHolder, $configManager, true);
    }

    public function testGetName()
    {
        $this->assertEquals(LayoutDataCollector::NAME, $this->dataCollector->getName());
    }

    public function testCollect()
    {
        $context = new LayoutContext();

        $contextItemInterface =  new ContextItemStub();
        $contextItems = [
            'string' => 'string',
            'array' => ['array'],
            'ContextItemInterface' => $contextItemInterface
        ];
        foreach ($contextItems as $name => $item) {
            $context->set($name, $item);
        }
        $contextItems['array'] = json_encode($contextItems['array']);
        $contextItems['ContextItemInterface'] = '(object) ContextItemStub::id:1';

        $contextData = [
            'string' => 'string',
            'array' => [],
            'object' => \stdClass::class
        ];
        foreach ($contextData as $name => $item) {
            $context->data()->set($name, $item);
        }

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));

        $this->dataCollector->setNotAppliedActions(['action1', 'action2']);
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $result = [
            'context' => [
                'items' => $contextItems,
                'data' => $contextData
            ],
            'views' => [],
            'count' => 0,
            'not_applied_actions_count' => 2,
            'not_applied_actions' => ['action1', 'action2']
        ];

        $this->assertEquals($result, $this->dataCollector->getData());
    }

    /**
     * @dataProvider blockOptionsProvider
     *
     * @param array $options
     * @param array $tree
     */
    public function testCollectBuildBlockOptions($options, $tree)
    {
        $rootBlock = new BlockView();
        $rootBlock->vars['id'] = key($tree);
        $blockViews = $this->getBlockViews($rootBlock, current($tree));

        foreach ($options as $id => $blockOptions) {
            $this->dataCollector->collectBuildBlockOptions($id, $id, $blockOptions);
        }

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
            $block = $this->createMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($blockView->vars['id']));

            $this->dataCollector->collectBlockTree($block, $blockView);
        }

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue(new LayoutContext()));

        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        foreach ($blockViews as $blockView) {
            $this->assertEquals($blockView->vars, $options[$blockView->vars['id']]);
        }
    }

    /**
     * @dataProvider blockOptionsProvider
     *
     * @param array $options
     * @param array $tree
     */
    public function testCollectBuildViewOptions($options, $tree)
    {
        $rootBlock = new BlockView();
        $rootBlock->vars['id'] = key($tree);
        $blockViews = $this->getBlockViews($rootBlock, current($tree));

        foreach ($options as $id => $blockOptions) {
            /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
            $block = $this->createMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($id));

            $this->dataCollector->collectBuildViewOptions($block, $id, $blockOptions);
        }

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
            $block = $this->createMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($blockView->vars['id']));

            $this->dataCollector->collectBlockTree($block, $blockView);
        }

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue(new LayoutContext()));

        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        foreach ($blockViews as $blockView) {
            $this->assertEquals($blockView->vars, $options[$blockView->vars['id']]);
        }
    }

    /**
     * @return array
     */
    public function blockOptionsProvider()
    {
        return [
            [
                'options' => [
                    'root' => [
                        'id' => 'root',
                        'attr' => [],
                        'string' => 'root_string',
                        'array' => ['root', 'array'],
                        'boolean' => true,
                        'object' => new \stdClass(),
                        'visible' => true
                    ],
                    'head' => [
                        'id' => 'head',
                        'string' => 'head_string',
                        'array' => ['head', 'array'],
                        'boolean' => false,
                        'object' => new \stdClass(),
                        'visible' => false
                    ],
                    'body' => [
                        'id' => 'body',
                        'string' => 'body_string',
                        'array' => ['body', 'array'],
                        'boolean' => true,
                        'object' => new \stdClass(),
                        'visible' => true
                    ]
                ],
                'tree' => [
                    'root' => [
                        'head' => [],
                        'body' => [
                            'undefined' => []
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param BlockView $rootBlock
     * @param array $tree
     * @param BlockView[] $blockViews
     *
     * @return BlockView[]
     */
    protected function getBlockViews(BlockView $rootBlock, $tree, &$blockViews = [])
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
    
    /**
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockRequest()
    {
        return $this->createMock(Request::class);
    }

    /**
     * @return Response|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockResponse()
    {
        return $this->createMock(Response::class);
    }
}
