<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

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

    public function testVisibleDefault()
    {
        $obj = new CalendarProperty();
        $this->assertEquals(true, $obj->getVisible());
    }

    public function testCalendarUid()
    {
        $obj = new CalendarProperty();

        $obj->setCalendarUid('test', 123);
        $this->assertEquals('test:123', $obj->getCalendarUid());

        $obj->setCalendarAlias('other');
        $this->assertEquals('other:123', $obj->getCalendarUid());

        $obj->setCalendarId(345);
        $this->assertEquals('other:345', $obj->getCalendarUid());
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
            array('calendarAlias', 'testAlias'),
            array('calendarId', 123),
            array('user', new User()),
            array('visible', false),
        );
    }
}
