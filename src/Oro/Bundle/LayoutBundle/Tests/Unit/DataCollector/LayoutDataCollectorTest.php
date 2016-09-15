<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\ContextItemInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;

class LayoutDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutContextHolder|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHolder;

    /** @var LayoutDataCollector */
    protected $dataCollector;

    protected function setUp()
    {
        $this->contextHolder = $this->getMock(LayoutContextHolder::class);

        $configs = [
            'oro_layout.debug_developer_toolbar' => true
        ];

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMock(ConfigManager::class, [], [], '', false);
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

        $contextItemInterface = $this->getMock(ContextItemInterface::class);
        $contextItemInterface->expects($this->once())
            ->method('toString')
            ->will($this->returnValue('ContextItemInterface'));
        $contextItems = [
            'string' => 'string',
            'array' => ['array'],
            'ContextItemInterface' => $contextItemInterface
        ];
        foreach ($contextItems as $name => $item) {
            $context->set($name, $item);
        }
        $contextItems['array'] = json_encode($contextItems['array']);
        $contextItems['ContextItemInterface'] = 'ContextItemInterface';

        $contextData = [
            'string' => 'string',
            'array' => ['array'],
            'object' => new \stdClass()
        ];
        foreach ($contextData as $name => $item) {
            $context->data()->set($name, $item);
        }
        $contextData['array'] = json_encode($contextData['array']);
        $contextData['object'] = get_class($contextData['object']);

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));

        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $result = [
            'context' => [
                'items' => $contextItems,
                'data' => $contextData
            ],
            'views' => [],
            'count' => 0
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

            /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
            $block = $this->getMock(BlockInterface::class);
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
            /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
            $block = $this->getMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($id));

            $this->dataCollector->collectBuildViewOptions($block, $id, $blockOptions);
        }

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
            $block = $this->getMock(BlockInterface::class);
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
    public function testCollectFinishViewOptions($options, $tree)
    {
        $rootBlock = new BlockView();
        $rootBlock->vars['id'] = key($tree);
        $blockViews = $this->getBlockViews($rootBlock, current($tree));

        foreach ($options as $id => $blockOptions) {
            /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
            $block = $this->getMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($id));

            $this->dataCollector->collectFinishViewOptions($block, $blockOptions);
        }

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
            $block = $this->getMock(BlockInterface::class);
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
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockRequest()
    {
        return $this->getMock(Request::class);
    }

    /**
     * @return Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockResponse()
    {
        return $this->getMock(Response::class);
    }
}
