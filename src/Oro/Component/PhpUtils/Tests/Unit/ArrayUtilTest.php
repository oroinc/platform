<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ArrayUtil;

class ArrayUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateOrderedComparator()
    {
        $order = array_flip(['a', 'z', 'd', 'e']);
        $array = [
            'b' => 'val b',
            'd' => 'val d',
            'z' => 'val z',
            'c' => 'val c',
            'e' => 'val e',
        ];
        $expectedResult = [
            'z' => 'val z',
            'd' => 'val d',
            'e' => 'val e',
            'b' => 'val b',
            'c' => 'val c',
        ];

        uksort($array, ArrayUtil::createOrderedComparator($order));
        $this->assertEquals(array_keys($expectedResult), array_keys($array));
        $this->assertEquals(array_values($expectedResult), array_values($array));
    }

    /**
     * @dataProvider isAssocDataProvider
     */
    public function testIsAssoc($array, $expectedResult)
    {
        $this->assertEquals($expectedResult, ArrayUtil::isAssoc($array));
    }

    public function isAssocDataProvider()
    {
        return [
            [[1, 2, 3], false],
            [[0 => 1, 1 => 2, 2 => 3], false],
            [['a' => 1, 'b' => 2, 'c' => 3], true],
            [[1, 'b' => 2, 3], true],
            [[1 => 1, 2 => 2, 3 => 3], true]
        ];
    }

    public function testSortByEmpty()
    {
        $array = [];

        ArrayUtil::sortBy($array);
        $this->assertSame([], $array);
    }

    public function testSortByArrayNoOrder()
    {
        $array = [
            ['name' => '1'],
            ['name' => '2'],
            ['name' => '3'],
        ];

        ArrayUtil::sortBy($array);
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

        ArrayUtil::sortBy($array, true);
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

        ArrayUtil::sortBy($array);
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

        ArrayUtil::sortBy($array, true);
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

        ArrayUtil::sortBy($array);
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

        ArrayUtil::sortBy($array, true);
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

        ArrayUtil::sortBy($array);
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

        ArrayUtil::sortBy($array, true);
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

        ArrayUtil::sortBy($array, false, 'name', SORT_STRING);
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

        ArrayUtil::sortBy($array, true, 'name', SORT_STRING);
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

        ArrayUtil::sortBy($array, false, 'name', SORT_STRING | SORT_FLAG_CASE);
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

        ArrayUtil::sortBy($array, true, 'name', SORT_STRING | SORT_FLAG_CASE);
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

        ArrayUtil::sortBy($array, false, '[child][priority]');
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

        ArrayUtil::sortBy($array);
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

        ArrayUtil::sortBy($array, false, 'child.priority');
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByClosure()
    {
        $obj1  = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2  = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3  = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [
            $obj1,
            $obj2,
            $obj3,
        ];

        ArrayUtil::sortBy(
            $array,
            false,
            function ($item) {
                return $item->priority;
            }
        );
        $this->assertSame(
            [
                $obj1,
                $obj3,
                $obj2,
            ],
            $array
        );
    }

    public function testSortByCallable()
    {
        $obj1  = $this->createObject(['name' => '1', 'priority' => null]);
        $obj2  = $this->createObject(['name' => '2', 'priority' => 100]);
        $obj3  = $this->createObject(['name' => '3', 'priority' => 0]);
        $array = [
            $obj1,
            $obj2,
            $obj3,
        ];

        ArrayUtil::sortBy(
            $array,
            false,
            [$this, 'getObjectPriority']
        );
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
     * @dataProvider someProvider
     */
    public function testSome(callable $callback, array $array, $expectedResult)
    {
        $this->assertSame($expectedResult, ArrayUtil::some($callback, $array));
    }

    public function someProvider()
    {
        return [
            [
                function ($item) {
                    return $item === 1;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 0;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 4;
                },
                [0, 1, 2, 3, 4],
                true,
            ],
            [
                function ($item) {
                    return $item === 5;
                },
                [0, 1, 2, 3, 4],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dropWhileProvider
     */
    public function testDropWhile(callable $callback, array $array, $expectedResult)
    {
        $this->assertEquals($expectedResult, ArrayUtil::dropWhile($callback, $array));
    }

    public function dropWhileProvider()
    {
        return [
            [
                function ($item) {
                    return $item !== 2;
                },
                [],
                [],
            ],
            [
                function ($item) {
                    return $item !== 2;
                },
                [0, 1, 2, 3, 4, 5],
                [2, 3, 4, 5],
            ],
            [
                function ($item) {
                    return $item !== 0;
                },
                [0, 1, 2, 3, 4, 5],
                [0, 1, 2, 3, 4, 5],
            ],
            [
                function ($item) {
                    return $item !== 6;
                },
                [0, 1, 2, 3, 4, 5],
                [],
            ],
        ];
    }

    /**
     * @param object $obj
     *
     * @return mixed
     */
    public function getObjectPriority($obj)
    {
        return $obj->priority;
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
}
