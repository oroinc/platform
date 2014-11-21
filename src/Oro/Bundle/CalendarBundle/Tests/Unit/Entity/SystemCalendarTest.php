<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Symfony\Component\PropertyAccess\PropertyAccess;

class SystemCalendarTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new SystemCalendar();
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
        $obj = new SystemCalendar();
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);

        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function testEvents()
    {
        $obj = new SystemCalendar();
        $event = new CalendarEvent();
        $obj->addEvent($event);
        $this->assertCount(1, $obj->getEvents());
        $events = $obj->getEvents();

        $this->assertTrue($event === $events[0]);
        $this->assertTrue($obj === $events[0]->getSystemCalendar());
    }

    public function testToString()
    {
        $obj = new SystemCalendar();
        $obj->setName('testName');
        $this->assertEquals($obj->getName(), (string)$obj);
    }

    public function propertiesDataProvider()
    {
        return [
            ['name', 'testName'],
            ['public', true],
            ['organization', new Organization()],
        ];
    }
}
