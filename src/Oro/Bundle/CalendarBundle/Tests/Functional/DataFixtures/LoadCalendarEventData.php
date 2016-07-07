<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class LoadCalendarEventData extends AbstractFixture implements DependentFixtureInterface
{
    const CALENDAR_EVENT_TITLE = 'Regular event not in start:end range';
    const CALENDAR_EVENT_WITH_ATTENDEE = 'Event with attendee';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData'
        ];
    }

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
        $fileName = __DIR__ . DIRECTORY_SEPARATOR . 'calendar_event_fixture.yml';
        $data = Yaml::parse(file_get_contents($fileName));

        foreach ($data as $item) {
            $start = date_create($item['start'], new \DateTimeZone('UTC'));
            $event = new CalendarEvent();
            $event->setCalendar($calendar)
                ->setTitle($item['title'])
                ->setStart($start)
                ->setEnd(date_create($item['end'], new \DateTimeZone('UTC')))
                ->setAllDay($item['allDay']);
            if (!empty($item['recurrence'])) {
                $recurrence = new Recurrence();
                $recurrence->setRecurrenceType($item['recurrence']['type'])
                    ->setInterval($item['recurrence']['interval'])
                    ->setOccurrences($item['recurrence']['occurrences'])
                    ->setTimeZone($item['recurrence']['timeZone'])
                    ->setStartTime(
                        date_create($item['recurrence']['startTime'], new \DateTimeZone('UTC'))
                    );
                $event->setRecurrence($recurrence);
            }
            if (!empty($item['exceptions'])) {
                foreach ($item['exceptions'] as $exceptionItem) {
                    $exception = new CalendarEvent();
                    $exception->setCalendar($calendar)
                        ->setTitle($exceptionItem['title'])
                        ->setStart(
                            date_create($exceptionItem['start'], new \DateTimeZone('UTC'))
                        )
                        ->setEnd(
                            date_create($exceptionItem['end'], new \DateTimeZone('UTC'))
                        )
                        ->setAllDay($exceptionItem['allDay'])
                        ->setCancelled($exceptionItem['isCancelled'])
                        ->setOriginalStart($start)
                        ->setRecurringEvent($event);
                    $event->addRecurringEventException($exception);
                }
            }
            $manager->persist($event);
            if (!empty($item['reference'])) {
                $this->setReference($item['reference'], $event);
            }
        }

        $event    = new CalendarEvent();
        $attendee = new Attendee();
        $userName = 'user'.mt_rand(0, 100);
        $attendee->setEmail($userName.'@example.com');
        $attendee->setDisplayName($userName);
        $attendee->setUser($this->getReference('simple_user'));
        $event->setCalendar($calendar)
            ->setTitle(self::CALENDAR_EVENT_WITH_ATTENDEE)
            ->setStart(
                date_create('+1 year', new \DateTimeZone('UTC'))
            )
            ->setEnd(
                date_create('+1 year', new \DateTimeZone('UTC'))
            )
            ->setAllDay($item['allDay']);
        $event->setRelatedAttendee($attendee);
        $manager->persist($event);

        $manager->flush();
    }
}
