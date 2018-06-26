<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartView;

class ChartViewTest extends \PHPUnit\Framework\TestCase
{
    const TEMPLATE = 'template.twig.html';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $data;

    protected $testVars = array('foo' => 'bar');

    /**
     * @var ChartView
     */
    protected $chartView;

    protected function setUp()
    {
        $this->twig = $this->createMock('Twig_Environment');
        $this->data = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $this->chartView = new ChartView(
            $this->twig,
            self::TEMPLATE,
            $this->data,
            $this->testVars
        );
    }

    public function testRender()
    {
        $expectedArrayData = array('bar' => 'baz');
        $expectedContext = array_merge($this->testVars, array('data' => $expectedArrayData));
        $expectedRenderResult = 'Rendered template';

        $this->data->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($expectedArrayData));

        $this->twig->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE, $expectedContext)
            ->will($this->returnValue($expectedRenderResult));

        $this->assertEquals($expectedRenderResult, $this->chartView->render());
    }
}
