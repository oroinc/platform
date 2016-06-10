<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

class FilterValueTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetPath()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        $this->assertSame('path', $filterValue->getPath());
        $filterValue->setPath('path2');
        $this->assertSame('path2', $filterValue->getPath());
    }

    public function testGetSetValue()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        $this->assertSame('value', $filterValue->getValue());

        $filterValue->setValue('value2');
        $this->assertSame('value2', $filterValue->getValue());

        $filterValue->setValue(['value1', 'value2']);
        $this->assertSame(['value1', 'value2'], $filterValue->getValue());
    }

    public function testGetSetOperator()
    {
        $filterValue = new FilterValue('path', 'value', 'operator');

        $this->assertSame('operator', $filterValue->getOperator());
        $filterValue->setOperator('operator2');
        $this->assertSame('operator2', $filterValue->getOperator());
    }

    public function testDefaultOperator()
    {
        $filterValue = new FilterValue('path', 'value');

        $this->assertNull($filterValue->getOperator());
    }
}
