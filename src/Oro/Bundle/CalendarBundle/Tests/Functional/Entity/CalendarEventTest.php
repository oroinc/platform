<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarEventTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    public function testRelatedAttendeeShouldBeRemoveFromChildEvent()
    {
        $em = $this->getEntityManager();

        $attendee = $this->createAttendee('event attendee');
        $childEventRelatedAttendee = $this->createAttendee('child event related attendee');

        $calendarEvent = $this->createCalendarEvent(
            'parent event',
            null,
            $attendee,
            [$childEventRelatedAttendee]
        );
        $em->persist($calendarEvent);

        $childCalendarEvent = $this->createCalendarEvent('child event', $calendarEvent, $childEventRelatedAttendee);
        $em->persist($childCalendarEvent);

        $em->flush();
        $em->refresh($calendarEvent);

        $this->assertCount(2, $calendarEvent->getAttendees()->toArray());

        $secondEventRelatedAttendeeId = $childEventRelatedAttendee->getId();

        $em->remove($childCalendarEvent);
        $em->flush();
        $em->clear();

        $this->assertNull($em->find('OroCalendarBundle:Attendee', $secondEventRelatedAttendeeId));
    }

    /**
     * @param string $displayName
     *
     * @return Attendee
     */
    protected function createAttendee($displayName)
    {
        $attendee = new Attendee();
        $attendee->setDisplayName($displayName);

        return $attendee;
    }

    /**
     * @param string $title
     * @param CalendarEvent $parent
     * @param Attendee|null $relatedAttendee
     * @param Attendee[] $attendees
     *
     * @return CalendarEvent
     */
    protected function createCalendarEvent(
        $title,
        CalendarEvent $parent = null,
        Attendee $relatedAttendee = null,
        array $attendees = []
    ) {
        $event = new CalendarEvent();
        $event->setTitle($title)
            ->setStart(new \DateTime())
            ->setEnd(new \DateTime())
            ->setAllDay(true)
            ->setParent($parent);

        foreach ($attendees as $attendee) {
            $event->addAttendee($attendee);
        }

        if ($relatedAttendee) {
            $event->setRelatedAttendee($relatedAttendee);
        }

        return $event;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCalendarBundle:CalendarEvent');
    }
}
