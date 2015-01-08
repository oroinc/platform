<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter;

class HttpEntityNameParameterFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $helper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()->setMethods(null)->getMock();

        $filter = new HttpEntityNameParameterFilter($helper);
        $this->assertEquals(
            'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity',
            $filter->filter('Oro_Bundle_SoapBundle_Tests_Unit_Entity_Manager_Stub_Entity', null)
        );
    }
}
