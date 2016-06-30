<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;

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

        $view = $this->getMockBlockView();
        $view->vars['id'] = 'root';

        $childView = $this->getMockBlockView([$view]);
        $childView->vars['id'] = 'head';

        $view->children[] = $childView;

        $layout = $this->getMockLayout();
        $layout->expects($this->once())
            ->method('getView')
            ->will($this->returnValue($view));

        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->dataCollector, ['views' => []]);

        $this->dataCollector->setLayout($layout);
        $this->dataCollector->collect($requset, $response);

        $data = $property->getValue($this->dataCollector);
        $this->assertEquals(['root' => ['head' => '~']], $data['views']);

    }

    public function testGetTree()
    {
        $class = new \ReflectionClass(LayoutDataCollector::class);
        $property = $class->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->dataCollector, ['views' => []]);

        $tree = current($this->dataCollector->getTree());
        $this->assertNotEmpty($tree['data']);
    }

    public function testBuildFinalViewTree()
    {
        $view = $this->getMockBlockView();
        $view->vars['id'] = 'root';

        $childView = $this->getMockBlockView([$view]);
        $childView->vars['id'] = 'head';

        $view->children[] = $childView;

        $class = new \ReflectionClass(LayoutDataCollector::class);

        $property = $class->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->dataCollector, ['views' => []]);

        $method = $class->getMethod('buildFinalViewTree');
        $method->setAccessible(true);
        $method->invoke($this->dataCollector, $view);

        $data = $property->getValue($this->dataCollector);
        $this->assertEquals(['root' => ['head' => '~']], $data['views']);
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

        $this->assertEquals(['head' => '~'], $result);
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
}
