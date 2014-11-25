<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

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
            array('title', 'testTitle'),
            array('description', 'testdDescription'),
            array('start', new \DateTime()),
            array('end', new \DateTime()),
            array('allDay', true),
            array('backgroundColor', '#FF0000'),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('invitationStatus', CalendarEvent::NOT_RESPONDED)
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testNotValidInvitationStatusSetter()
    {
        $obj = new CalendarEvent();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, 'invitationStatus', 'wrong');
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
        $event = new CalendarEvent();
        $event->setInvitationStatus($status);
        $actual = $event->getAvailableInvitationStatuses();
        $this->assertEmpty(array_diff($expected, $actual));
    }

    /**
     * @return array
     */
    public function getAvailableDataProvider()
    {
        return [
            'not responded' => [
                'status' => CalendarEvent::NOT_RESPONDED,
                'expected' => [
                    CalendarEvent::ACCEPTED,
                    CalendarEvent::TENTATIVELY_ACCEPTED,
                    CalendarEvent::DECLINED,
                ]
            ],
            'declined' => [
                'status' => CalendarEvent::DECLINED,
                'expected' => [
                    CalendarEvent::ACCEPTED,
                    CalendarEvent::TENTATIVELY_ACCEPTED,
                ]
            ],
            'accepted' => [
                'status' => CalendarEvent::ACCEPTED,
                'expected' => [
                    CalendarEvent::TENTATIVELY_ACCEPTED,
                    CalendarEvent::DECLINED,
                ]
            ],
            'tentatively available ' => [
                'status' => CalendarEvent::TENTATIVELY_ACCEPTED,
                'expected' => [
                    CalendarEvent::ACCEPTED,
                    CalendarEvent::DECLINED,
                ]
            ]
        ];
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
}
