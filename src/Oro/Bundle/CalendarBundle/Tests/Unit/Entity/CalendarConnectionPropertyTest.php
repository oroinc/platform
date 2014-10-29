<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\CalendarConnectionProperty;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarConnectionPropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new CalendarConnectionProperty();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    public function testVisibleDefault()
    {
        $obj = new CalendarConnectionProperty();
        $this->assertEquals(true, $obj->getVisible());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new CalendarConnectionProperty();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return array(
            array('calendar', '12345'),
            array('owner', new User()),
            array('visible', true),
        );
    }
}
