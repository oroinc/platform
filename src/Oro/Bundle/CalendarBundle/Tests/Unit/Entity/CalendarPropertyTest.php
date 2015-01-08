<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarPropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new CalendarProperty();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    public function testPositionDefault()
    {
        $obj = new CalendarProperty();
        $this->assertSame(0, $obj->getPosition());
    }

    public function testVisibleDefault()
    {
        $obj = new CalendarProperty();
        $this->assertTrue($obj->getVisible());
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new CalendarProperty();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return array(
            array('targetCalendar', new Calendar()),
            array('calendarAlias', 'testAlias'),
            array('calendar', 123),
            array('position', 100),
            array('visible', false),
            array('backgroundColor', '#FFFFFF'),
        );
    }
}
