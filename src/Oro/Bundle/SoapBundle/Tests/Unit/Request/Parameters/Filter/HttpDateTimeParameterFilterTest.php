<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;

class HttpDateTimeParameterFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilter()
    {
        $filter = new HttpDateTimeParameterFilter();

        $rawValue = '2010-01-28T15:00:00 02:00';
        $this->assertEquals(new \DateTime('2010-01-28T15:00:00+02:00'), $filter->filter($rawValue, null));

        $rawValue = '2010-01-28T15:00:00+02:00';
        $this->assertEquals(new \DateTime('2010-01-28T15:00:00+02:00'), $filter->filter($rawValue, null));
    }
}
