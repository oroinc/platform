<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;

class ArrayUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testSortByEmpty()
    {
        $array = [];

        ArrayUtils::sortBy($array);
        $this->assertSame([], $array);
    }

    public function testSortByArrayNoOrder()
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3'],
        ];

        ArrayUtils::sortBy($array);
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '3'],
            ],
            $array
        );
    }

    public function testSortByArrayNoOrderReverse()
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3'],
        ];

        ArrayUtils::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '3'],
            ],
            $array
        );
    }

    public function testSortByArraySameOrder()
    {
        $array = [
            ['name' => '1', 'priority' => 100],
            ['name' => '2', 'priority' => 100],
            ['name' => '3', 'priority' => 100],
        ];

        ArrayUtils::sortBy($array);
        $this->assertSame(
            [
                ['name' => '1', 'priority' => 100],
                ['name' => '2', 'priority' => 100],
                ['name' => '3', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArraySameOrderReverse()
    {
        $array = [
            ['name' => '1', 'priority' => 100],
            ['name' => '2', 'priority' => 100],
            ['name' => '3', 'priority' => 100],
        ];

        ArrayUtils::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '1', 'priority' => 100],
                ['name' => '2', 'priority' => 100],
                ['name' => '3', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArrayNumeric()
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3', 'priority' => 100],
            ['name' => '4'],
            ['name' => '5', 'priority' => -100],
            ['name' => '6', 'priority' => 100],
            ['name' => '7', 'priority' => -100],
            ['name' => '8', 'priority' => 0],
            ['name' => '9'],
        ];

        ArrayUtils::sortBy($array);
        $this->assertSame(
            [
                ['name' => '5', 'priority' => -100],
                ['name' => '7', 'priority' => -100],
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '4'],
                ['name' => '8', 'priority' => 0],
                ['name' => '9'],
                ['name' => '3', 'priority' => 100],
                ['name' => '6', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByArrayNumericReverse()
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3', 'priority' => 100],
            ['name' => '4'],
            ['name' => '5', 'priority' => -100],
            ['name' => '6', 'priority' => 100],
            ['name' => '7', 'priority' => -100],
            ['name' => '8', 'priority' => 0],
            ['name' => '9'],
        ];

        ArrayUtils::sortBy($array, true);
        $this->assertSame(
            [
                ['name' => '3', 'priority' => 100],
                ['name' => '6', 'priority' => 100],
                ['name' => '1'],
                ['name' => '2'],
                ['name' => '4'],
                ['name' => '8', 'priority' => 0],
                ['name' => '9'],
                ['name' => '5', 'priority' => -100],
                ['name' => '7', 'priority' => -100],
            ],
            $array
        );
    }

    public function testSortByAssocArrayNumeric()
    {
        $array = [
            'i1' => ['name' => '1'],
            'i2' => ['name' => '2'],
            'i3' => ['name' => '3', 'priority' => 100],
            'i4' => ['name' => '4'],
            'i5' => ['name' => '5', 'priority' => -100],
            'i6' => ['name' => '6', 'priority' => 100],
            'i7' => ['name' => '7', 'priority' => -100],
            'i8' => ['name' => '8', 'priority' => 0],
            'i9' => ['name' => '9'],
        ];

        ArrayUtils::sortBy($array);
        $this->assertSame(
            [
                'i5' => ['name' => '5', 'priority' => -100],
                'i7' => ['name' => '7', 'priority' => -100],
                'i1' => ['name' => '1'],
                'i2' => ['name' => '2'],
                'i4' => ['name' => '4'],
                'i8' => ['name' => '8', 'priority' => 0],
                'i9' => ['name' => '9'],
                'i3' => ['name' => '3', 'priority' => 100],
                'i6' => ['name' => '6', 'priority' => 100],
            ],
            $array
        );
    }

    public function testSortByAssocArrayNumericReverse()
    {
        $array = [
            'i1' => ['name' => '1'],
            'i2' => ['name' => '2'],
            'i3' => ['name' => '3', 'priority' => 100],
            'i4' => ['name' => '4'],
            'i5' => ['name' => '5', 'priority' => -100],
            'i6' => ['name' => '6', 'priority' => 100],
            'i7' => ['name' => '7', 'priority' => -100],
            'i8' => ['name' => '8', 'priority' => 0],
            'i9' => ['name' => '9'],
        ];

        ArrayUtils::sortBy($array, true);
        $this->assertSame(
            [
                'i3' => ['name' => '3', 'priority' => 100],
                'i6' => ['name' => '6', 'priority' => 100],
                'i1' => ['name' => '1'],
                'i2' => ['name' => '2'],
                'i4' => ['name' => '4'],
                'i8' => ['name' => '8', 'priority' => 0],
                'i9' => ['name' => '9'],
                'i5' => ['name' => '5', 'priority' => -100],
                'i7' => ['name' => '7', 'priority' => -100],
            ],
            $array
        );
    }

    public function testSortByArrayString()
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ];

        ArrayUtils::sortBy($array, false, 'name', SORT_STRING);
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            $array
        );
    }

    public function testSortByArrayStringReverse()
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ];

        ArrayUtils::sortBy($array, true, 'name', SORT_STRING);
        $this->assertSame(
            [
                ['name' => 'c'],
                ['name' => 'b'],
                ['name' => 'a'],
            ],
            $array
        );
    }

    public function testSortByArrayStringCaseInsensitive()
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'C'],
            ['name' => 'B'],
        ];

        ArrayUtils::sortBy($array, false, 'name', SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'B'],
                ['name' => 'C'],
            ],
            $array
        );
    }

    public function testSortByArrayStringCaseInsensitiveReverse()
    {
        $array = [
            ['name' => 'a'],
            ['name' => 'C'],
            ['name' => 'B'],
        ];

        ArrayUtils::sortBy($array, true, 'name', SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame(
            [
                ['name' => 'C'],
                ['name' => 'B'],
                ['name' => 'a'],
            ],
            $array
        );
    }

    public function testSortByArrayPath()
    {
        $array = [
            ['name' => '1', 'child' => ['priority' => 1]],
            ['name' => '2', 'child' => ['priority' => 3]],
            ['name' => '3', 'child' => ['priority' => 2]],
        ];

        ArrayUtils::sortBy($array, false, '[child][priority]');
        $this->assertSame(
            [
                ['name' => '1', 'child' => ['priority' => 1]],
                ['name' => '3', 'child' => ['priority' => 2]],
                ['name' => '2', 'child' => ['priority' => 3]],
            ],
            $array
        );
    }

    public function testSortByObject()
    {
        $obj1  = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2  = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3  = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [
            $obj1,
            $obj2,
            $obj3,
        ];

        ArrayUtils::sortBy($array);
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByObjectPath()
    {
        $obj1  = $this->createObject(
            ['name' => '1', 'child' => $this->createObject(['priority' => null])]
        );
        $obj2  = $this->createObject(
            ['name' => '2', 'child' => $this->createObject(['priority' => 100])]
        );
        $obj3  = $this->createObject(
            ['name' => '3', 'child' => $this->createObject(['priority' => 0])]
        );
        $array = [
            $obj1,
            $obj2,
            $obj3,
        ];

        ArrayUtils::sortBy($array, false, 'child.priority');
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    /**
     * @param array $properties
     *
     * @return object
     */
    protected function createObject($properties)
    {
        $obj = new \stdClass();
        foreach ($properties as $name => $val) {
            $obj->$name = $val;
        }

        return $obj;
    }

    /**
     * @param array $array
     * @param mixed $columnKey
     * @param mixed $indexKey
     * @param array $expected
     *
     * @dataProvider arrayColumnProvider
     */
    public function testArrayColumn(array $array, $columnKey, $indexKey, array $expected)
    {
        $this->assertEquals(
            $expected,
            ArrayUtils::arrayColumn($array, $columnKey, $indexKey)
        );
    }

    /**
     * @return array
     */
    public function arrayColumnProvider()
    {
        return [
            'empty'        => [[], 'value', 'value', []],
            'no_index'     => [
                [
                    [
                        'id'    => 'id1',
                        'value' => 'value2'
                    ]
                ],
                'value',
                null,
                ['value2']
            ],
            'index'        => [
                [
                    [
                        'id'    => 'id1',
                        'value' => 'value2'
                    ]
                ],
                'value',
                'id',
                ['id1' => 'value2']
            ],
            'wrong_index'  => [
                [
                    ['value' => 'value2']
                ],
                'value',
                'id',
                []
            ],
            'wrong_column' => [
                [
                    ['value' => 'value2']
                ],
                'id',
                null,
                []
            ],

        ];
    }

    /**
     * @param array  $array
     * @param mixed  $columnKey
     * @param mixed  $indexKey
     * @param string $expectedMessage
     *
     * @dataProvider arrayColumnInputData
     */
    public function testArrayColumnInputData(array $array, $columnKey, $indexKey, $expectedMessage)
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            $expectedMessage
        );

        ArrayUtils::arrayColumn($array, $columnKey, $indexKey);
    }

    /**
     * @return array
     */
    public function arrayColumnInputData()
    {
        return [
            'empty_column_key' => [
                [
                    ['id' => 'value']
                ],
                null,
                null,
                'Column key is empty'
            ]
        ];
    }

    /**
     * @dataProvider mergeDataProvider
     *
     * @param array $expected
     * @param array $first
     * @param array $second
     */
    public function testArrayMergeRecursiveDistinct(array $expected, array $first, array $second)
    {
        $this->assertEquals($expected, ArrayUtils::arrayMergeRecursiveDistinct($first, $second));
    }

    /**
     * @return array
     */
    public function mergeDataProvider()
    {
        return [
            [
                [
                    'a',
                    'b',
                    'c' => [
                        'd' => 'd2',
                        'e' => 'e1'
                    ]
                ],
                ['a', 'c' => ['d' => 'd1', 'e' => 'e1']],
                ['b', 'c' => ['d' => 'd2']]
            ]
        ];
    }
}
