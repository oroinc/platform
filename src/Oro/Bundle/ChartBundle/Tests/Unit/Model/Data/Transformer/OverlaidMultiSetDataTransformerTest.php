<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\OverlaidMultiSetDataTransformer;

class OverlaidMultiSetDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getTransformDataProvider
     */
    public function testTransform(array $originalData, array $expected): void
    {
        $transformer = new OverlaidMultiSetDataTransformer();

        self::assertEquals($expected, $transformer->transform(new ArrayData($originalData), [])->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTransformDataProvider(): array
    {
        return [
            'empty data' => [
                'originalData' => [],
                'expected' => [],
            ],
            'only empty dateRange1 data' => [
                'originalData' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => null,
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => null,
                        ],
                    ],
                    // date ranges may be missing in case of empty data
                ],
                'expected' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => null,
                            'originalLabel' => '2023-01-23',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => null,
                            'originalLabel' => '2023-01-24',
                        ],
                    ],
                ],
            ],
            'empty dateRange1 data and dateRange2 data' => [
                'originalData' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => null,
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => null,
                        ],
                    ],
                    'Jan 25, 2023 - Jan 26, 2023' => [
                        [
                            'label' => '2023-01-25',
                            'value' => '300',
                        ],
                        [
                            'label' => '2023-01-26',
                            'value' => '400',
                        ],
                    ],
                    // date ranges may be missing in case of empty data
                ],
                'expected' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => null,
                            'originalLabel' => '2023-01-23',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => null,
                            'originalLabel' => '2023-01-24',
                        ],
                    ],
                    'Jan 25, 2023 - Jan 26, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => '300',
                            'originalLabel' => '2023-01-25',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => '400',
                            'originalLabel' => '2023-01-26',
                        ],
                    ],
                ],
            ],
            'full data' => [
                'originalData' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => '100',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => '200',
                        ],
                    ],
                    'Jan 24, 2023 - Jan 25, 2023' => [
                        [
                            'label' => '2023-01-24',
                            'value' => '200',
                        ],
                        [
                            'label' => '2023-01-25',
                            'value' => '300',
                        ],
                    ],
                    'Jan 25, 2023 - Jan 26, 2023' => [
                        [
                            'label' => '2023-01-25',
                            'value' => '300',
                        ],
                        [
                            'label' => '2023-01-26',
                            'value' => '400',
                        ],
                    ],
                ],
                'expected' => [
                    'Jan 23, 2023 - Jan 24, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => '100',
                            'originalLabel' => '2023-01-23',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => '200',
                            'originalLabel' => '2023-01-24',
                        ],
                    ],
                    'Jan 24, 2023 - Jan 25, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => '200',
                            'originalLabel' => '2023-01-24',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => '300',
                            'originalLabel' => '2023-01-25',
                        ],
                    ],
                    'Jan 25, 2023 - Jan 26, 2023' => [
                        [
                            'label' => '2023-01-23',
                            'value' => '300',
                            'originalLabel' => '2023-01-25',
                        ],
                        [
                            'label' => '2023-01-24',
                            'value' => '400',
                            'originalLabel' => '2023-01-26',
                        ],
                    ],
                ],
            ],
            'full data with range in tooltip' => [
                'originalData' => [
                    'Jan 1, 2023 - Feb 28, 2023' => [
                        [
                            'label' => '2023-01-01',
                            'value' => '100',
                            'startLabel' => '2023-01-01',
                            'endLabel' => '2023-01-01',
                        ],
                        [
                            'label' => '2023-01-02',
                            'value' => '200',
                            'startLabel' => '2023-01-02',
                            'endLabel' => '2023-01-08',
                        ],
                        // ...
                    ],
                    'Jan 2, 2023 - Mar 1, 2023' => [
                        [
                            'label' => '2023-01-02',
                            'value' => '200',
                            'startLabel' => '2023-01-02',
                            'endLabel' => '2023-01-08',
                        ],
                        [
                            'label' => '2023-01-09',
                            'value' => '300',
                            'startLabel' => '2023-01-09',
                            'endLabel' => '2023-01-15',
                        ],
                        // ...
                    ],
                    'Jan 3, 2023 - Mar 2, 2023' => [
                        [
                            'label' => '2023-01-03',
                            'value' => '200',
                            'startLabel' => '2023-01-03',
                            'endLabel' => '2023-01-08',
                        ],
                        [
                            'label' => '2023-01-09',
                            'value' => '300',
                            'startLabel' => '2023-01-09',
                            'endLabel' => '2023-01-15',
                        ],
                        // ...
                    ],
                ],
                'expected' => [
                    'Jan 1, 2023 - Feb 28, 2023' => [
                        [
                            'label' => '2023-01-01',
                            'value' => '100',
                            'originalLabel' => '2023-01-01',
                            'startLabel' => '2023-01-01',
                            'endLabel' => '2023-01-01',
                        ],
                        [
                            'label' => '2023-01-02',
                            'value' => '200',
                            'originalLabel' => '2023-01-02',
                            'startLabel' => '2023-01-02',
                            'endLabel' => '2023-01-08',
                        ],
                    ],
                    'Jan 2, 2023 - Mar 1, 2023' => [
                        [
                            'label' => '2023-01-01',
                            'value' => '200',
                            'originalLabel' => '2023-01-02',
                            'startLabel' => '2023-01-02',
                            'endLabel' => '2023-01-08',
                        ],
                        [
                            'label' => '2023-01-02',
                            'value' => '300',
                            'originalLabel' => '2023-01-09',
                            'startLabel' => '2023-01-09',
                            'endLabel' => '2023-01-15',
                        ],
                    ],
                    'Jan 3, 2023 - Mar 2, 2023' => [
                        [
                            'label' => '2023-01-01',
                            'value' => '200',
                            'originalLabel' => '2023-01-03',
                            'startLabel' => '2023-01-03',
                            'endLabel' => '2023-01-08',
                        ],
                        [
                            'label' => '2023-01-02',
                            'value' => '300',
                            'originalLabel' => '2023-01-09',
                            'startLabel' => '2023-01-09',
                            'endLabel' => '2023-01-15',
                        ],
                    ],
                ],
            ],
        ];
    }
}
