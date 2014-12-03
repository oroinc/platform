<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new CalendarEvent();
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
        $obj = new CalendarEvent();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertSame($value, $accessor->getValue($obj, $property));
    }

    public function testPrePersist()
    {
        $obj = new CalendarEvent();

        $this->assertNull($obj->getCreatedAt());
        $this->assertNull($obj->getUpdatedAt());

        $obj->prePersist();
        $this->assertInstanceOf('\DateTime', $obj->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new CalendarEvent();

        $this->assertNull($obj->getUpdatedAt());

        $obj->preUpdate();
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testGetReminderData()
    {
        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $obj->setTitle('testTitle');
        $calendar = new Calendar();
        $calendar->setOwner(new User());
        $obj->setCalendar($calendar);
        /** @var ReminderData $reminderData */
        $reminderData = $obj->getReminderData();

        $this->assertEquals($reminderData->getSubject(), $obj->getTitle());
        $this->assertEquals($reminderData->getExpireAt(), $obj->getStart());
        $this->assertTrue($reminderData->getRecipient() === $calendar->getOwner());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Only user's calendar events can have reminders. Event Id: 1.
     */
    public function testGetReminderDataWithLogicException()
    {
        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $obj->getReminderData();
    }

    public function testToString()
    {
        $obj = new CalendarEvent();
        $obj->setTitle('testTitle');
        $this->assertEquals($obj->getTitle(), (string)$obj);
    }

    public function propertiesDataProvider()
    {
        return [
            ['calendar', new Calendar()],
            ['systemCalendar', new SystemCalendar()],
            ['reminders', new ArrayCollection([new Reminder()])],
            ['title', 'testTitle'],
            ['description', 'testDescription'],
            ['start', new \DateTime()],
            ['end', new \DateTime()],
            ['allDay', true],
            ['backgroundColor', '#FFFFFF'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];
    }

    public function testGetCalendarUidNoCalendar()
    {
        $obj = new CalendarEvent();
        $this->assertNull($obj->getCalendarUid());
    }

    public function testGetCalendarUidUserCalendar()
    {
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 123);

        $obj = new CalendarEvent();
        $obj->setCalendar($calendar);
        $this->assertEquals('user_123', $obj->getCalendarUid());
    }

    public function testGetCalendarUidSystemCalendar()
    {
        $calendar = new SystemCalendar();
        ReflectionUtil::setId($calendar, 123);

        $obj = new CalendarEvent();
        $obj->setSystemCalendar($calendar);
        $this->assertEquals('system_123', $obj->getCalendarUid());
    }

    public function testGetCalendarUidPublicCalendar()
    {
        $calendar = new SystemCalendar();
        ReflectionUtil::setId($calendar, 123);
        $calendar->setPublic(true);

        $obj = new CalendarEvent();
        $obj->setSystemCalendar($calendar);
        $this->assertEquals('public_123', $obj->getCalendarUid());
    }

    public function testSetCalendar()
    {
        $calendar       = new Calendar();
        $systemCalendar = new SystemCalendar();

        $obj = new CalendarEvent();

        $this->assertNull($obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setCalendar($calendar);
        $this->assertSame($calendar, $obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setSystemCalendar($systemCalendar);
        $this->assertNull($obj->getCalendar());
        $this->assertSame($systemCalendar, $obj->getSystemCalendar());

        $obj->setCalendar($calendar);
        $this->assertSame($calendar, $obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setCalendar(null);
        $this->assertNull($obj->getCalendar());

        $obj->setSystemCalendar($systemCalendar);
        $this->assertNull($obj->getCalendar());
        $this->assertSame($systemCalendar, $obj->getSystemCalendar());

        $obj->setSystemCalendar(null);
        $this->assertNull($obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());
    }
}
