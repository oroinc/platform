<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\PieChartDataTransformer;

class PieChartDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PieChartDataTransformer
     */
    protected $transformer;

    protected function setUp()
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

    public function transformDataProvider()
    {
        return array(
            array(
                'data' => new ArrayData(
                    array(
                        array('label' => 'Foo', 'value' => 50),
                        array('label' => 'Foo', 'value' => 200),
                        array('label' => 'Foo', 'value' => 250),
                    )
                ),
                'chartOptions' => array(
                    'settings' => array(
                        PieChartDataTransformer::FRACTION_INPUT_DATA_FIELD => 'value',
                        PieChartDataTransformer::FRACTION_OUTPUT_DATA_FIELD => 'fraction',
                    )
                ),
                'expectedData' => new ArrayData(
                    array(
                        array('label' => 'Foo', 'value' => 50, 'fraction' => 0.1),
                        array('label' => 'Foo', 'value' => 200, 'fraction' => 0.4),
                        array('label' => 'Foo', 'value' => 250, 'fraction' => 0.5),
                    )
                )
            )
        );
    }
}
