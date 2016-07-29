<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\EventListener\ChildEventsSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEnumValue;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;

class ChildEventsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChildEventsSubscriber */
    protected $childEventsSubscriber;

    public function setUp()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['find', 'findDefaultCalendars'])
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            }));
        $repository->expects($this->any())
            ->method('findDefaultCalendars')
            ->will($this->returnCallback(function ($userIds) {
                return array_map(
                    function ($userId) {
                        return (new Calendar())
                            ->setOwner(new User($userId));
                    },
                    $userIds
                );
            }));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['Extend\Entity\EV_Ce_Attendee_Status', null, $repository],
                ['Extend\Entity\EV_Ce_Attendee_Type', null, $repository],
                ['OroCalendarBundle:Calendar', null, $repository],
            ]));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->childEventsSubscriber = new ChildEventsSubscriber(
            $registry,
            $securityFacade
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::POST_SUBMIT => 'postSubmit',
                FormEvents::PRE_SUBMIT => 'preSubmit',
            ],
            $this->childEventsSubscriber->getSubscribedEvents()
        );
    }

    public function testOnSubmit()
    {
        // set default empty data
        $firstEvent = new CalendarEvent();
        $firstEvent->setTitle('1');
        $secondEvent = new CalendarEvent();
        $secondEvent->setTitle('2');
        $eventWithoutRelatedAttendee = new CalendarEvent();
        $eventWithoutRelatedAttendee->setTitle('3');

        $parentEvent = new CalendarEvent();
        $parentEvent->setTitle('parent title')
            ->setRelatedAttendee(new Attendee())
            ->setDescription('parent description')
            ->setStart(new \DateTime('now'))
            ->setEnd(new \DateTime('now'))
            ->setAllDay(true);
        $parentEvent->addChildEvent($firstEvent)
            ->addChildEvent($secondEvent)
            ->addChildEvent($eventWithoutRelatedAttendee);

        $firstEvent->setRelatedAttendee(
            (new Attendee())
                ->setEmail('first@example.com')
        );
        $secondEvent->setRelatedAttendee(
            (new Attendee())
                ->setEmail('second@example.com')
        );

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($parentEvent));

        // assert default data with default status
        $this->childEventsSubscriber->postSubmit(new FormEvent($form, []));

        $this->assertEquals(CalendarEvent::STATUS_ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $secondEvent->getInvitationStatus());
        $this->assertEquals(null, $eventWithoutRelatedAttendee->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);
        $this->assertEventDataEquals($parentEvent, $eventWithoutRelatedAttendee);

        // modify data
        $parentEvent->setTitle('modified title')
            ->setDescription('modified description')
            ->setStart(new \DateTime('tomorrow'))
            ->setEnd(new \DateTime('tomorrow'))
            ->setAllDay(false);

        $parentEvent->getRelatedAttendee()->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_ACCEPTED, CalendarEvent::STATUS_ACCEPTED)
        );
        $firstEvent->getRelatedAttendee()->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_DECLINED, CalendarEvent::STATUS_DECLINED)
        );
        $secondEvent->getRelatedAttendee()->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_TENTATIVE, CalendarEvent::STATUS_TENTATIVE)
        );

        // assert modified data
        $this->childEventsSubscriber->postSubmit(new FormEvent($form, []));

        $this->assertEquals(CalendarEvent::STATUS_ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_DECLINED, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_TENTATIVE, $secondEvent->getInvitationStatus());
        $this->assertEquals(null, $eventWithoutRelatedAttendee->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);
        $this->assertEventDataEquals($parentEvent, $eventWithoutRelatedAttendee);
    }

    public function testRelatedAttendees()
    {
        $user = new User();

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setUser($user)
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($event));

        $this->childEventsSubscriber->postSubmit(new FormEvent($form, []));

        $this->assertEquals($attendees->first(), $event->getRelatedAttendee());
    }

    public function testAddEvents()
    {
        $user = new User(1);
        $user2 = new User(2);

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setUser($user),
            (new Attendee())
                ->setUser($user2)
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($event));

        $this->childEventsSubscriber->postSubmit(new FormEvent($form, []));

        $this->assertCount(1, $event->getChildEvents());
        $this->assertSame($attendees->get(1), $event->getChildEvents()->first()->getRelatedAttendee());
    }

    public function testUpdateAttendees()
    {
        $user = (new User())
            ->setFirstName('first')
            ->setLastName('last');

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setEmail('attendee@example.com')
                ->setUser($user),
            (new Attendee())
                ->setEmail('attendee2@example.com')
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($event));

        $this->childEventsSubscriber->postSubmit(new FormEvent($form, []));

        $this->assertEquals('attendee@example.com', $attendees->get(0)->getDisplayName());
        $this->assertEquals('attendee2@example.com', $attendees->get(1)->getDisplayName());
    }

    /**
     * @param CalendarEvent $expected
     * @param CalendarEvent $actual
     */
    protected function assertEventDataEquals(CalendarEvent $expected, CalendarEvent $actual)
    {
        $this->assertEquals($expected->getTitle(), $actual->getTitle());
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
        $this->assertEquals($expected->getStart(), $actual->getStart());
        $this->assertEquals($expected->getEnd(), $actual->getEnd());
        $this->assertEquals($expected->getAllDay(), $actual->getAllDay());
    }
}
