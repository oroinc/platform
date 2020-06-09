<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Twig\Environment;

class ChartViewTest extends \PHPUnit\Framework\TestCase
{
    const TEMPLATE = 'template.twig.html';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    protected $twig;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DataInterface
     */
    protected $data;

    /**
     * @var array
     */
    protected $testVars = ['foo' => 'bar'];

    /**
     * @var ChartView
     */
    protected $chartView;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->data = $this->createMock(DataInterface::class);
        $this->chartView = new ChartView(
            $this->twig,
            self::TEMPLATE,
            $this->data,
            $this->testVars
        );
    }

    public function testRender()
    {
        $rawData = ['bar' => 'baz <HELLO>', 'test' => 'string', 'value' => 10.0];
        $expectedArrayData = ['bar' => htmlentities('baz <HELLO>'), 'test' => 'string', 'value' => 10.0];
        $expectedContext = array_merge($this->testVars, ['data' => $expectedArrayData]);
        $expectedRenderResult = 'Rendered template';

        $this->data->expects($this->once())
            ->method('toArray')
            ->willReturn($rawData);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE, $expectedContext)
            ->willReturn($expectedRenderResult);

        $this->assertEquals($expectedRenderResult, $this->chartView->render());
    }
}
