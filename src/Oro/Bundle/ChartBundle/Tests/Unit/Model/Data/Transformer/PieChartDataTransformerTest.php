<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\PieChartDataTransformer;

class PieChartDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PieChartDataTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new PieChartDataTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(DataInterface $data, array $chartOptions, DataInterface $expectedData)
    {
        $this->assertEquals($expectedData->toArray(), $this->transformer->transform($data, $chartOptions)->toArray());
    }

    public function transformDataProvider(): array
    {
        return [
            [
                'data' => new ArrayData(
                    [
                        ['label' => 'Foo', 'value' => 50],
                        ['label' => 'Foo', 'value' => 200],
                        ['label' => 'Foo', 'value' => 250],
                    ]
                ),
                'chartOptions' => [
                    'settings' => [
                        PieChartDataTransformer::FRACTION_INPUT_DATA_FIELD => 'value',
                        PieChartDataTransformer::FRACTION_OUTPUT_DATA_FIELD => 'fraction',
                    ]
                ],
                'expectedData' => new ArrayData(
                    [
                        ['label' => 'Foo', 'value' => 50, 'fraction' => 0.1],
                        ['label' => 'Foo', 'value' => 200, 'fraction' => 0.4],
                        ['label' => 'Foo', 'value' => 250, 'fraction' => 0.5],
                    ]
                )
            ]
        ];
    }
}
