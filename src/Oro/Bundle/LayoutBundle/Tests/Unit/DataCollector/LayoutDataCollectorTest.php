<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\ContextItemInterface;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;

class LayoutDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LayoutDataCollector
     */
    protected $dataCollector;

    protected function setUp()
    {
        $this->dataCollector = new LayoutDataCollector();
    }

    public function testGetName()
    {
        $this->assertEquals(LayoutDataCollector::NAME, $this->dataCollector->getName());
    }

    public function testCollect()
    {
        $requset = $this->getMockRequest();
        $response = $this->getMockResponse();
        $this->dataCollector->collect($requset, $response);

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('data');
        $property->setAccessible(true);

        $data = $property->getValue($this->dataCollector);

        $this->assertArrayHasKey('views', $data);
        $this->assertArrayHasKey('items', $data);
    }

    public function testCollectViews()
    {
        $view = $this->getMockBlockView();
        $view->vars['id'] = 'root';

        $childView = $this->getMockBlockView([$view]);
        $childView->vars['id'] = 'head';

        $view->children[] = $childView;

        $this->dataCollector->collectViews($view);

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('views');
        $property->setAccessible(true);

        $this->assertEquals(['root' => ['head' => []]], $property->getValue($this->dataCollector));
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

        $context = $this->getMockLayoutContext();

        $class = new \ReflectionClass(LayoutContext::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($context, $items);

        $this->dataCollector->collectContextItems($context);

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('contextItems');
        $property->setAccessible(true);

        $items['array'] = json_encode($items['array']);
        $items['ContextItemInterface'] = 'ContextItemInterface';
        $this->assertEquals($items, $property->getValue($this->dataCollector));
    }

    public function testGetViews()
    {
        $views = ['root' => ['head' => []]];

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->dataCollector, ['views' => $views]);

        $this->assertEquals($views, $this->dataCollector->getViews());
    }

    public function testGetItems()
    {
        $items = [
            'string' => 'string',
            'array' => ['array'],
        ];

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->dataCollector, ['items' => $items]);

        $this->assertEquals($items, $this->dataCollector->getItems());
    }

    public function testRecursiveBuildFinalViewTree()
    {
        $view = $this->getMockBlockView();
        $view->vars['id'] = 'root';

        $childView = $this->getMockBlockView([$view]);
        $childView->vars['id'] = 'head';

        $view->children[] = $childView;

        $result = [];

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $method = $class->getMethod('recursiveBuildFinalViewTree');
        $method->setAccessible(true);
        $method->invokeArgs($this->dataCollector, [$view, &$result]);

        $this->assertEquals(['head' => []], $result);
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
     * @return Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLayout()
    {
        return $this->getMock('Oro\Component\Layout\Layout', [], [], '', false);
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
     * @return LayoutContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLayoutContext()
    {
        return $this->getMock('Oro\Component\Layout\LayoutContext');
    }

    /**
     * @return ContextItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockContextItemInterface()
    {
        return $this->getMock('Oro\Component\Layout\ContextItemInterface');
    }
}
