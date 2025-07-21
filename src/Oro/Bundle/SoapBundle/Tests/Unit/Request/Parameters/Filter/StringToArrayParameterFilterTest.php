<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use PHPUnit\Framework\TestCase;

class StringToArrayParameterFilterTest extends TestCase
{
    public function testFilterWithDefaultSeparator(): void
    {
        $filter = new StringToArrayParameterFilter();

        $rawValue = 'value1,value2,value3';
        $this->assertEquals(['value1', 'value2', 'value3'], $filter->filter($rawValue, null));
    }

    public function testFilterWithCustomSeparator(): void
    {
        $filter = new StringToArrayParameterFilter('|');

        $rawValue = 'value1|value2|value3';
        $this->assertEquals(['value1', 'value2', 'value3'], $filter->filter($rawValue, null));
    }
}
