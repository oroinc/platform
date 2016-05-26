<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
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

    public function propertiesDataProvider()
    {
        return array(
            array('calendar', new Calendar()),
            array('systemCalendar', new SystemCalendar()),
            array('title', 'testTitle'),
            array('description', 'testdDescription'),
            array('start', new \DateTime()),
            array('end', new \DateTime()),
            array('allDay', true),
            array('backgroundColor', '#FF0000'),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
        );
    }

    public function testInvitationStatus()
    {
        $attendee = new Attendee();
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setRelatedAttendee($attendee);

        $attendee->setStatus(new TestEnumValue(CalendarEvent::STATUS_ACCEPTED, CalendarEvent::STATUS_ACCEPTED));
        $this->assertEquals(CalendarEvent::ACCEPTED, $calendarEvent->getInvitationStatus());

        $attendee->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_TENTATIVE, CalendarEvent::STATUS_TENTATIVE)
        );
        $this->assertEquals(CalendarEvent::STATUS_TENTATIVE, $calendarEvent->getInvitationStatus());
    }

    public function testChildren()
    {
        $calendarEventOne = new CalendarEvent();
        $calendarEventOne->setTitle('First calendar event');
        $calendarEventTwo = new CalendarEvent();
        $calendarEventOne->setTitle('Second calendar event');
        $calendarEventThree = new CalendarEvent();
        $calendarEventOne->setTitle('Third calendar event');
        $children = array($calendarEventOne, $calendarEventTwo);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('Parent calendar event');

        // reset children calendar events
        $this->assertSame($calendarEvent, $calendarEvent->resetChildEvents($children));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals($children, $actual->toArray());
        /** @var CalendarEvent $child */
        foreach ($children as $child) {
            $this->assertEquals($calendarEvent->getTitle(), $child->getParent()->getTitle());
        }

        // add children calendar events
        $this->assertSame($calendarEvent, $calendarEvent->addChildEvent($calendarEventTwo));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals($children, $actual->toArray());

        $this->assertSame($calendarEvent, $calendarEvent->addChildEvent($calendarEventThree));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals(array($calendarEventOne, $calendarEventTwo, $calendarEventThree), $actual->toArray());
        /** @var CalendarEvent $child */
        foreach ($children as $child) {
            $this->assertEquals($calendarEvent->getTitle(), $child->getParent()->getTitle());
        }

        // remove child calender event
        $this->assertSame($calendarEvent, $calendarEvent->removeChildEvent($calendarEventOne));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals(array(1 => $calendarEventTwo, 2 => $calendarEventThree), $actual->toArray());
    }

    /**
     * @param $status
     * @param $expected
     *
     * @dataProvider getAvailableDataProvider
     */
    public function testGetAvailableInvitationStatuses($status, $expected)
    {
        $attendee = new Attendee();
        $attendee->setStatus(new TestEnumValue($status, $status));

        $event = new CalendarEvent();
        $event->setRelatedAttendee($attendee);
        $actual = $event->getAvailableInvitationStatuses();
        $this->assertEmpty(array_diff($expected, $actual));
    }

    /**
     * @return array
     */
    public function getAvailableDataProvider()
    {
        return [
            'not responded'          => [
                'status'   => CalendarEvent::STATUS_NONE,
                'expected' => [
                    CalendarEvent::STATUS_ACCEPTED,
                    CalendarEvent::STATUS_TENTATIVE,
                    CalendarEvent::STATUS_DECLINED,
                ]
            ],
            'declined'               => [
                'status'   => CalendarEvent::STATUS_DECLINED,
                'expected' => [
                    CalendarEvent::STATUS_ACCEPTED,
                    CalendarEvent::STATUS_TENTATIVE,
                ]
            ],
            'accepted'               => [
                'status'   => CalendarEvent::STATUS_ACCEPTED,
                'expected' => [
                    CalendarEvent::STATUS_TENTATIVE,
                    CalendarEvent::STATUS_DECLINED,
                ]
            ],
            'tentatively available ' => [
                'status'   => CalendarEvent::STATUS_TENTATIVE,
                'expected' => [
                    CalendarEvent::STATUS_ACCEPTED,
                    CalendarEvent::STATUS_DECLINED,
                ]
            ]
        ];
    }

    public function testGetChildEventByCalendar()
    {
        $firstCalendar = new Calendar();
        $firstCalendar->setName('1');
        $secondCalendar = new Calendar();
        $secondCalendar->setName('2');

        $firstEvent = new CalendarEvent();
        $firstEvent->setTitle('1')
            ->setCalendar($firstCalendar);
        $secondEvent = new CalendarEvent();
        $secondEvent->setTitle('2')
            ->setCalendar($secondCalendar);

        $masterEvent = new CalendarEvent();
        $masterEvent->addChildEvent($firstEvent)
            ->addChildEvent($secondEvent);

        $this->assertEquals($firstEvent, $masterEvent->getChildEventByCalendar($firstCalendar));
        $this->assertEquals($secondEvent, $masterEvent->getChildEventByCalendar($secondCalendar));
        $this->assertNull($masterEvent->getChildEventByCalendar(new Calendar));
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

    public function testIsUpdatedFlags()
    {
        $date          = new \DateTime('2012-12-12 12:12:12');
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUpdatedAt($date);

        $this->assertTrue($calendarEvent->isUpdatedAtSet());
    }

    public function testIsNotUpdatedFlags()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUpdatedAt(null);

        $this->assertFalse($calendarEvent->isUpdatedAtSet());
    }

    public function testAttendees()
    {
        $attendee  = $this->getMock('Oro\Bundle\CalendarBundle\Entity\Attendee');
        $attendees = new ArrayCollection([$attendee]);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setAttendees($attendees);

        $this->assertCount(1, $calendarEvent->getAttendees());

        $calendarEvent->addAttendee(clone $attendee);

        $this->assertCount(2, $calendarEvent->getAttendees());

        foreach ($calendarEvent->getAttendees() as $item) {
            $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\Attendee', $item);
        }

        $calendarEvent->removeAttendee($attendee);

        $this->assertCount(1, $calendarEvent->getAttendees());
    }

    public function testRelatedAttendee()
    {
        $attendee      = $this->getMock('Oro\Bundle\CalendarBundle\Entity\Attendee');
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setRelatedAttendee($attendee);

        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\Attendee', $calendarEvent->getRelatedAttendee());
    }

    /**
     * @dataProvider childAttendeesProvider
     */
    public function testGetChildAttendees(CalendarEvent $event, array $expectedAttendees)
    {
        $this->assertEquals($expectedAttendees, array_values($event->getChildAttendees()->toArray()));
    }

    public function childAttendeesProvider()
    {
        $attendee1 = (new Attendee())->setEmail('first@example.com');
        $attendee2 = (new Attendee())->setEmail('second@example.com');
        $attendee3 = (new Attendee())->setemail('third@example.com');

        return [
            'event without realted attendee' => [
                (new CalendarEvent())
                    ->setAttendees(new ArrayCollection([
                        $attendee1,
                        $attendee2,
                        $attendee3
                    ])),
                [
                    $attendee1,
                    $attendee2,
                    $attendee3
                ],
            ],
            'event with related attendee' => [
                (new CalendarEvent())
                    ->setAttendees(new ArrayCollection([
                        $attendee1,
                        $attendee2,
                        $attendee3
                    ]))
                    ->setRelatedAttendee($attendee1),
                [
                    $attendee2,
                    $attendee3
                ],
            ]
        ];
    }
}
