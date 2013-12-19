<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Oro\Bundle\SidebarBundle\Entity\SidebarState;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SidebarStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new SidebarState();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        $user = $this->getMockForAbstractClass('Symfony\Component\Security\Core\User\UserInterface');
        return array(
            array('user', $user),
            array('position', 'SIDEBAR_RIGHT'),
            array('state', 'SIDEBAR_MINIMIZED'),
        );
    }
}
