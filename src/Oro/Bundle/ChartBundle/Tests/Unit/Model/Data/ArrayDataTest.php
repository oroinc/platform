<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;

class ArrayDataTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $arrayData = array('foo' => 'bar');
        $data = new ArrayData($arrayData);
        $this->assertEquals($arrayData, $data->toArray());
    }
}
