<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;

class StringToArrayParameterFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterWithDefaultSeparator()
    {
        $filter = new StringToArrayParameterFilter();

        $rawValue = 'value1,value2,value3';
        $this->assertEquals(['value1', 'value2', 'value3'], $filter->filter($rawValue, null));
    }

    public function testFilterWithCustomSeparator()
    {
        $filter = new StringToArrayParameterFilter('|');

        $rawValue = 'value1|value2|value3';
        $this->assertEquals(['value1', 'value2', 'value3'], $filter->filter($rawValue, null));
    }
}
