<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Utils;

use Oro\Bundle\ImportExportBundle\Utils\ArrayUtil;
use PHPUnit\Framework\TestCase;

class ArrayUtilTest extends TestCase
{
    /**
     * @dataProvider filterEmptyArraysDataProvider
     */
    public function testFilterEmptyArrays(array $expected, array $data): void
    {
        $this->assertEquals($expected, ArrayUtil::filterEmptyArrays($data));
    }

    public function filterEmptyArraysDataProvider(): array
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
