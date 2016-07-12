<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\ContextItemInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;

class LayoutDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LayoutDataCollector
     */
    protected $dataCollector;

    protected function setUp()
    {
        $configs = [
            'oro_layout.debug_developer_toolbar' => true
        ];

        $configManager = $this->getMockConfigManager();
        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($code) use ($configs) {
                return $configs[$code];
            }));
        $this->dataCollector = new LayoutDataCollector($configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(LayoutDataCollector::NAME, $this->dataCollector->getName());
    }

    public function testCollect()
    {
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $this->assertTrue(is_array($this->dataCollector->getViews()));
        $this->assertTrue(is_array($this->dataCollector->getItems()));
    }

    public function testCollectViews()
    {
        $view = $this->getMockBlockView();
        $view->vars['id'] = 'root';

        $childView = $this->getMockBlockView([$view]);
        $childView->vars['id'] = 'head';

        $view->children[] = $childView;

        $this->dataCollector->collectViews($view);
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());

        $this->assertEquals(['root' => ['head' => []]], $this->dataCollector->getViews());
    }

    public function testCollectContextItems()
    {
        $contextItemInterface = $this->getMockContextItemInterface();
        $contextItemInterface->expects($this->once())
            ->method('toString')
            ->will($this->returnValue('ContextItemInterface'));

        $items = [
            'string' => 'string',
            'array' => ['array'],
            'ContextItemInterface' => $contextItemInterface
        ];

        $context = new LayoutContext();
        $context->set('string', $items['string']);
        $context->set('array', $items['array']);
        $context->set('ContextItemInterface', $items['ContextItemInterface']);

        $this->dataCollector->collectContextItems($context);

        $items['array'] = json_encode($items['array']);
        $items['ContextItemInterface'] = 'ContextItemInterface';

        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());
        $this->assertEquals($items, $this->dataCollector->getItems());
    }

    public function testGetViews()
    {
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());
        $this->assertTrue(is_array($this->dataCollector->getViews()));
    }

    public function testGetItems()
    {
        $this->dataCollector->collect($this->getMockRequest(), $this->getMockResponse());
        $this->assertTrue(is_array($this->dataCollector->getItems()));
    }

    /**
     * @return ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockConfigManager()
    {
        return $this->getMock('Oro\Bundle\ConfigBundle\Config\ConfigManager', [], [], '', false);
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockRequest()
    {
        return $this->getMock('Symfony\Component\HttpFoundation\Request');
    }

    /**
     * @return Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockResponse()
    {
        return $this->getMock('Symfony\Component\HttpFoundation\Response');
    }

    /**
     * @param array $args
     * @return BlockView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockBlockView(array $args = [])
    {
        return $this->getMock('Oro\Component\Layout\BlockView', [], $args, '', false);
    }

    /**
     * @return ContextItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockContextItemInterface()
    {
        return $this->getMock('Oro\Component\Layout\ContextItemInterface');
    }
}
