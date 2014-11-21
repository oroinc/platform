<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class LoadCalendarEventData extends AbstractFixture
{
    const CALENDAR_EVENT_TITLE = 'Test calendar event';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $calendar = $manager->getRepository('OroCalendarBundle:Calendar')->findDefaultCalendar(
            $user->getId(),
            $organization->getId()
        );


        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);
        $calendarEvent->setTitle(self::CALENDAR_EVENT_TITLE);
        $calendarEvent->setStart(new \DateTime('tomorrow'));
        $calendarEvent->setEnd(new \DateTime('tomorrow + 1hour'));
        $calendarEvent->setAllDay(false);

        $manager->persist($calendarEvent);
        $manager->flush();
    }
}
