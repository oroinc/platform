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
}
