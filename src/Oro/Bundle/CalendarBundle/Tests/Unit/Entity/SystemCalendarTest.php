<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj      = new SystemCalendar();
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);

        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['name', 'testName'],
            ['backgroundColor', '#FFFFFF'],
            ['public', true],
            ['createdAt', new \DateTime('now')],
            ['updatedAt', new \DateTime('now')],
        ];
    }

    public function testPrePersist()
    {
        $obj = new SystemCalendar();

        $this->assertNull($obj->getCreatedAt());
        $this->assertNull($obj->getUpdatedAt());

        $obj->prePersist();
        $this->assertInstanceOf('\DateTime', $obj->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new SystemCalendar();

        $this->assertNull($obj->getUpdatedAt());

        $obj->preUpdate();
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testEvents()
    {
        $obj   = new SystemCalendar();
        $event = new CalendarEvent();
        $obj->addEvent($event);
        $this->assertCount(1, $obj->getEvents());
        $events = $obj->getEvents();

        $this->assertSame($event, $events[0]);
        $this->assertSame($obj, $events[0]->getSystemCalendar());
    }

    public function testToString()
    {
        $obj = new SystemCalendar();
        $obj->setName('testName');
        $this->assertEquals($obj->getName(), (string)$obj);
    }

    public function testSetOrganizationForNonPublic()
    {
        $organization = new Organization();

        $obj = new SystemCalendar();

        $this->assertFalse($obj->isPublic());
        $this->assertNull($obj->getOrganization());
        $obj->setOrganization($organization);
        $this->assertSame($organization, $obj->getOrganization());
    }

    public function testSetOrganizationForPublic()
    {
        $obj = new SystemCalendar();
        $obj->setPublic(true);

        $this->assertTrue($obj->isPublic());
        $this->assertNull($obj->getOrganization());
        $obj->setOrganization(new Organization());
        $this->assertNull($obj->getOrganization());
    }
}
