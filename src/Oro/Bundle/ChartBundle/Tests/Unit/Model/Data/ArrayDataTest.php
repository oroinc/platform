<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use PHPUnit\Framework\TestCase;

class ArrayDataTest extends TestCase
{
    public function testToArray(): void
    {
        $arrayData = ['foo' => 'bar'];
        $data = new ArrayData($arrayData);
        $this->assertEquals($arrayData, $data->toArray());
    }
}
