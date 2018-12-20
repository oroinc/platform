<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\BooleanParameterFilter;

class BooleanParameterFilterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider filterDataProvider
     *
     * @param $expected
     * @param $rawValue
     */
    public function testFilter($expected, $rawValue)
    {
        $filter = new BooleanParameterFilter();

        $this->assertSame($expected, $filter->filter($rawValue, null));
    }

    public function filterDataProvider()
    {
        return [
            [false, 'false'],
            [false, 'no'],
            [false, 'off'],
            [false, false],
            [false, 0],
            [false, '0'],
            [false, ''],
            [null, '123'],
            [null, 123],
            [false, null],
            [true, 'true'],
            [true, 'yes'],
            [true, 'on'],
            [true, true],
            [true, 1],
            [true, '1'],
        ];
    }
}
