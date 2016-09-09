<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
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
use Oro\Bundle\CalendarBundle\Entity\Recurrence;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            ['calendar', new Calendar()],
            ['systemCalendar', new SystemCalendar()],
            ['title', 'testTitle'],
            ['description', 'testdDescription'],
            ['start', new \DateTime()],
            ['end', new \DateTime()],
            ['allDay', true],
            ['backgroundColor', '#FF0000'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['recurrence', new Recurrence()],
            ['originalStart', new \DateTime()],
            ['cancelled', true],
            ['parent', new CalendarEvent()],
            ['recurringEvent', new CalendarEvent()],
            ['relatedAttendee', new Attendee()],
            ['reminders', new ArrayCollection()],
        ];
    }

    public function testInvitationStatus()
    {
        $attendee      = new Attendee();
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setRelatedAttendee($attendee);

        $attendee->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_ACCEPTED, CalendarEvent::STATUS_ACCEPTED)
        );
        $this->assertEquals(CalendarEvent::ACCEPTED, $calendarEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_ACCEPTED, $calendarEvent->getRelatedAttendee()->getStatus());

        $attendee->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_TENTATIVE, CalendarEvent::STATUS_TENTATIVE)
        );
        $this->assertEquals(CalendarEvent::TENTATIVELY_ACCEPTED, $calendarEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_TENTATIVE, $calendarEvent->getRelatedAttendee()->getStatus());
    }

    public function testChildren()
    {
        $calendarEventOne = new CalendarEvent();
        $calendarEventOne->setTitle('First calendar event');
        $calendarEventTwo = new CalendarEvent();
        $calendarEventOne->setTitle('Second calendar event');
        $calendarEventThree = new CalendarEvent();
        $calendarEventOne->setTitle('Third calendar event');
        $children = [$calendarEventOne, $calendarEventTwo];

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
        $this->assertEquals([$calendarEventOne, $calendarEventTwo, $calendarEventThree], $actual->toArray());
        /** @var CalendarEvent $child */
        foreach ($children as $child) {
            $this->assertEquals($calendarEvent->getTitle(), $child->getParent()->getTitle());
        }

        // remove child calender event
        $this->assertSame($calendarEvent, $calendarEvent->removeChildEvent($calendarEventOne));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([1 => $calendarEventTwo, 2 => $calendarEventThree], $actual->toArray());
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
                ],
            ],
            'declined'               => [
                'status'   => CalendarEvent::STATUS_DECLINED,
                'expected' => [
                    CalendarEvent::STATUS_ACCEPTED,
                    CalendarEvent::STATUS_TENTATIVE,
                ],
            ],
            'accepted'               => [
                'status'   => CalendarEvent::STATUS_ACCEPTED,
                'expected' => [
                    CalendarEvent::STATUS_TENTATIVE,
                    CalendarEvent::STATUS_DECLINED,
                ],
            ],
            'tentatively available ' => [
                'status'   => CalendarEvent::STATUS_TENTATIVE,
                'expected' => [
                    CalendarEvent::STATUS_ACCEPTED,
                    CalendarEvent::STATUS_DECLINED,
                ],
            ],
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
        $this->assertSame($reminderData->getRecipient(), $calendar->getOwner());
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
     *
     * @param CalendarEvent $event
     * @param array         $expectedAttendees
     */
    public function testGetChildAttendees(CalendarEvent $event, array $expectedAttendees)
    {
        $this->assertEquals($expectedAttendees, array_values($event->getChildAttendees()->toArray()));
    }

    /**
     * @return array
     */
    public function childAttendeesProvider()
    {
        $attendee1 = (new Attendee())->setEmail('first@example.com');
        $attendee2 = (new Attendee())->setEmail('second@example.com');
        $attendee3 = (new Attendee())->setEmail('third@example.com');

        return [
            'event without realted attendee' => [
                (new CalendarEvent())
                    ->setAttendees(
                        new ArrayCollection(
                            [
                                $attendee1,
                                $attendee2,
                                $attendee3,
                            ]
                        )
                    ),
                [
                    $attendee1,
                    $attendee2,
                    $attendee3,
                ],
            ],
            'event with related attendee'    => [
                (new CalendarEvent())
                    ->setAttendees(
                        new ArrayCollection(
                            [
                                $attendee1,
                                $attendee2,
                                $attendee3,
                            ]
                        )
                    )
                    ->setRelatedAttendee($attendee1),
                [
                    $attendee2,
                    $attendee3,
                ],
            ],
        ];
    }

    public function testExceptions()
    {
        $exceptionOne = new CalendarEvent();
        $exceptionOne->setTitle('First calendar event exception');
        $exceptionTwo = new CalendarEvent();
        $exceptionOne->setTitle('Second calendar event exception');
        $exceptionThree = new CalendarEvent();
        $exceptionOne->setTitle('Third calendar event exception');
        $exceptions = [$exceptionOne, $exceptionTwo];

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('Exception parent calendar event');

        // reset exceptions
        $this->assertSame($calendarEvent, $calendarEvent->resetRecurringEventExceptions($exceptions));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals($exceptions, $actual->toArray());
        /** @var CalendarEvent $exception */
        foreach ($exceptions as $exception) {
            $this->assertEquals($calendarEvent->getTitle(), $exception->getRecurringEvent()->getTitle());
        }

        // add exception calendar events
        $this->assertSame($calendarEvent, $calendarEvent->addRecurringEventException($exceptionTwo));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals($exceptions, $actual->toArray());

        $this->assertSame($calendarEvent, $calendarEvent->addRecurringEventException($exceptionThree));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$exceptionOne, $exceptionTwo, $exceptionThree], $actual->toArray());
        /** @var CalendarEvent $exception */
        foreach ($exceptions as $exception) {
            $this->assertEquals($calendarEvent->getTitle(), $exception->getRecurringEvent()->getTitle());
        }

        // remove exception from calender event
        $this->assertSame($calendarEvent, $calendarEvent->removeRecurringEventException($exceptionOne));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([1 => $exceptionTwo, 2 => $exceptionThree], $actual->toArray());
    }

    public function testGetCurrentAttendees()
    {
        $one = new CalendarEvent();
        $one->setTitle('First calendar event');
        $two = new CalendarEvent();
        $two->setTitle('Second calendar event');
        $two->setParent($one);

        $one->addAttendee(new Attendee(1));
        $one->addAttendee(new Attendee(2));
        $one->addAttendee(new Attendee(3));
        $one->addAttendee(new Attendee(4));

        $this->assertCount(4, $one->getAttendees());
        $this->assertCount(4, $two->getAttendees());
        
        $this->assertCount(4, $one->getCurrentAttendees());
        $this->assertCount(0, $two->getCurrentAttendees());

        $one->setCurrentAttendees(new ArrayCollection([new Attendee(5), new Attendee(6)]));
        $two->setCurrentAttendees(new ArrayCollection([new Attendee(7), new Attendee(8)]));

        $this->assertCount(2, $one->getCurrentAttendees());
        $this->assertCount(2, $two->getCurrentAttendees());
    }
}
