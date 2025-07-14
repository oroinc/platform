<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ChartViewTest extends TestCase
{
    private const TEMPLATE = 'template.twig.html';
    private const TEST_VARS = ['foo' => 'bar'];

    private Environment&MockObject $twig;
    private DataInterface&MockObject $data;
    private ChartView $chartView;

    #[\Override]
    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->data = $this->createMock(DataInterface::class);
        $this->chartView = new ChartView(
            $this->twig,
            self::TEMPLATE,
            $this->data,
            self::TEST_VARS
        );
    }

    public function testRender(): void
    {
        $rawData = ['bar' => 'baz <HELLO>', 'test' => 'string', 'value' => 10.0];
        $expectedArrayData = ['bar' => htmlentities('baz <HELLO>'), 'test' => 'string', 'value' => 10.0];
        $expectedContext = array_merge(self::TEST_VARS, ['data' => $expectedArrayData]);
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
