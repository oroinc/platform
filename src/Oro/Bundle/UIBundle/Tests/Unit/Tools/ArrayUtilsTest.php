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
}
