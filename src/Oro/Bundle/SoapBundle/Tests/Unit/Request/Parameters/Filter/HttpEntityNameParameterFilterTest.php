<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter;

class HttpEntityNameParameterFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $helper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $helper->expects($this->once())
            ->method('resolveEntityClass')
            ->with('Test_Entity')
            ->willReturn('Test\Entity');

        $filter = new HttpEntityNameParameterFilter($helper);
        $this->assertEquals(
            'Test\Entity',
            $filter->filter('Test_Entity', null)
        );
    }
}
