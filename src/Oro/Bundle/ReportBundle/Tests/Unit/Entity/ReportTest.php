<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity;

use Oro\Bundle\ReportBundle\Entity\Report;

class ReportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        $obj = new Report();

        call_user_func_array(array($obj, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($expected, call_user_func_array(array($obj, 'get' . ucfirst($property)), array()));
    }

    public function getSetDataProvider()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        return array(
            'organization' => array('organization', $organization, $organization)
        );
    }
}
