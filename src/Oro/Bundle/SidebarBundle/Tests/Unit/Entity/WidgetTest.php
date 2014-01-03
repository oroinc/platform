<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Oro\Bundle\SidebarBundle\Entity\Widget;
use Symfony\Component\PropertyAccess\PropertyAccess;

class WidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Widget();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        $user = $this->getMockForAbstractClass('Symfony\Component\Security\Core\User\UserInterface');
        return array(
            array('user', $user),
            array('widgetName', 'test'),
            array('placement', 'left'),
            array('position', 1),
            array('state', 'WIDGET_MAXIMIZED_HOVER'),
            array('settings', array('a' => 'b')),
        );
    }
}
