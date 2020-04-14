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
use Symfony\Component\VarDumper\Cloner\Data;

class LayoutDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutContextHolder|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextHolder;

    /** @var LayoutDataCollector */
    protected $dataCollector;

    protected function setUp(): void
    {
        $this->contextHolder = $this->createMock(LayoutContextHolder::class);

        $configs = [
            'oro_layout.debug_developer_toolbar' => true,
        ];

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($code) use ($configs) {
                        return $configs[$code];
                    }
                )
            );

        $this->dataCollector = new LayoutDataCollector($this->contextHolder, $configManager, true);
    }

    public function testGetName()
    {
        $this->assertEquals(LayoutDataCollector::NAME, $this->dataCollector->getName());
    }

    public function testCollect()
    {
        $context = new LayoutContext();

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

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $notAppliedActions =  [
            [
                'name' => 'add',
                'args' => [
                    'id' => 'customer_sidebar_request',
                    'parentId' => 'customer_sidebar',
                    'blockType' => 'link',
                    'options' => [
                        'visible' => 'true',
                        'attr' => ['class'=> 'btn'],
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
            ]
        ];
        $this->dataCollector->setNotAppliedActions($notAppliedActions);
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $this->assertEquals($contextItems, $this->dataCollector->getData()['context']['items']);
        $this->assertArrayHasKey('views', $this->dataCollector->getData());
        $this->assertEquals(0, $this->dataCollector->getData()['count']);
        $this->assertEquals(2, $this->dataCollector->getData()['not_applied_actions_count']);
        $this->assertEquals($notAppliedActions, $this->dataCollector->getData()['not_applied_actions']);
        foreach ($this->dataCollector->getData()['context']['data'] as $datum) {
            $this->assertInstanceOf(Data::class, $datum);
        }
    }

    public function testCollectBuildViews()
    {
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

        $rootBlock = new BlockView();
        $rootBlock->vars['id'] = key($tree);
        $blockViews = $this->getBlockViews($rootBlock, current($tree));

        foreach ($blockViews as $blockView) {
            $blockView->vars = $options[$blockView->vars['id']];

            /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
            $block = $this->createMock(BlockInterface::class);
            $block->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($blockView->vars['id']));

            $this->dataCollector->collectBlockView($block, $blockView);
        }

        $this->contextHolder
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue(new LayoutContext()));

        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $data = $this->dataCollector->getData();
        $this->assertEquals('root', $data['views']['root']['id']);
        $this->assertCount(7, $data['views']['root']['view_vars']);
        $this->assertEquals(['root', '_root'], $data['views']['root']['block_prefixes']);
        $this->assertCount(2, $data['views']['root']['children']);
    }

    /**
     * @param BlockView   $rootBlock
     * @param array       $tree
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
