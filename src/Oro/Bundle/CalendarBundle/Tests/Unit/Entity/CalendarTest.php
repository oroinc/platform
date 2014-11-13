<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new Calendar();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Calendar();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function testEvents()
    {
        $obj = new Calendar();
        $event = new CalendarEvent();
        $obj->addEvent($event);
        $this->assertCount(1, $obj->getEvents());
        $events = $obj->getEvents();
        $this->assertTrue($event === $events[0]);
        $this->assertTrue($obj === $events[0]->getCalendar());
    }

    public function propertiesDataProvider()
    {
        return array(
            array('name', 'testName'),
            array('owner', new User())
        );
    }
}
