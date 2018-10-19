<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Utils;

use Oro\Bundle\ImportExportBundle\Utils\ArrayUtil;

class ArrayUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider filterEmptyArraysDataProvider
     *
     * @param array $expected
     * @param array $data
     */
    public function testFilterEmptyArrays(array $expected, array $data)
    {
        $this->assertEquals($expected, ArrayUtil::filterEmptyArrays($data));
    }

    /**
     * @return array
     */
    public function filterEmptyArraysDataProvider()
    {
        return [
            'One-level array data' => [
                'expected' => [
                    'One Level Array' => [
                        'rowOne' => 'some value',
                        'rowTwo' => 'some value',
                    ],
                    'Empty Array'     => [],
                    'Empty SubArrays' => [
                        'rowOne'  => 'some value',
                        'rowTree' => [
                            'subrowTwo'  => 'some value',
                            'subrowTree' => null,
                        ],
                    ],
                ],
                'data'     => [
                    'One Level Array' => [
                        'rowOne' => 'some value',
                        'rowTwo' => 'some value',
                    ],
                    'Empty Array'     => [],
                    'Empty SubArrays' => [
                        'rowOne'  => 'some value',
                        'rowTwo'  => [],
                        'rowTree' => [
                            'subrowOne'  => [],
                            'subrowTwo'  => 'some value',
                            'subrowTree' => null,
                        ],
                    ],
                ],
            ],
        ];
    }
}
