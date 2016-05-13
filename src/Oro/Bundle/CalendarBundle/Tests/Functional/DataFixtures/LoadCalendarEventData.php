<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class LoadCalendarEventData extends AbstractFixture
{
    const CALENDAR_EVENT_TITLE = 'Test regular event not in start:end range';

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

        // Test regular event not in start:end range.
        $regularEventNotInRange = new CalendarEvent();
        $regularEventNotInRange->setCalendar($calendar);
        $regularEventNotInRange->setTitle(self::CALENDAR_EVENT_TITLE);
        $regularEventNotInRange->setStart(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day')))
        );
        $regularEventNotInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day + 1 hour')))
        );
        $regularEventNotInRange->setAllDay(false);
        $manager->persist($regularEventNotInRange);

        // Test regular event in start:end range.
        $regularEventInRange = new CalendarEvent();
        $regularEventInRange->setCalendar($calendar);
        $regularEventInRange->setTitle('Test regular event in start:end range');
        $regularEventInRange->setStart(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-3 day')))
        );
        $regularEventInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-3 day + 1 hour')))
        );
        $regularEventInRange->setAllDay(false);
        $manager->persist($regularEventInRange);

        // Test recurring event not in start:end range.
        $eventNotInRange= new CalendarEvent();
        $eventNotInRange->setCalendar($calendar);
        $eventNotInRange->setTitle('Test recurring event not in start:end range');
        $eventNotInRange->setStart(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day')))
        );
        $eventNotInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day + 1 hour')))
        );
        $eventNotInRange->setAllDay(false);
        $recurrenceNotInRange = new Recurrence();
        $recurrenceNotInRange->setRecurrenceType(Recurrence::TYPE_DAILY);
        $recurrenceNotInRange->setInterval(1);
        $recurrenceNotInRange->setOccurrences(2);
        $recurrenceNotInRange->setStartTime(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day')))
        );
        $eventNotInRange->setRecurrence($recurrenceNotInRange);
        $manager->persist($eventNotInRange);

        // Test recurring event in start:end range.
        $eventInRange = new CalendarEvent();
        $eventInRange->setCalendar($calendar);
        $eventInRange->setTitle('Test recurring event in start:end range');
        $eventInRange->setStart(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-4 day')))
        );
        $eventInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-4 day + 1 hour')))
        );
        $eventInRange->setAllDay(false);
        $recurrenceInRange = new Recurrence();
        $recurrenceInRange->setRecurrenceType(Recurrence::TYPE_DAILY);
        $recurrenceInRange->setInterval(1);
        $recurrenceInRange->setOccurrences(2);
        $recurrenceInRange->setStartTime(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-4 day')))
        );
        $eventInRange->setRecurrence($recurrenceInRange);
        $manager->persist($eventInRange);

        // Test recurring event not in start:end range with exception in range.
        $eventNotInRangeWithExceptionInRange = new CalendarEvent();
        $eventNotInRangeWithExceptionInRange->setCalendar($calendar);
        $eventNotInRangeWithExceptionInRange->setTitle(
            'Test recurring event not in start:end range with exception in range'
        );
        $eventNotInRangeWithExceptionInRangeStart = new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day')));
        $eventNotInRangeWithExceptionInRange->setStart($eventNotInRangeWithExceptionInRangeStart);
        $eventNotInRangeWithExceptionInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-7 day + 1 hour')))
        );
        $eventNotInRangeWithExceptionInRange->setAllDay(false);
        $recurrenceNotInRangeWithException = new Recurrence();
        $recurrenceNotInRangeWithException->setRecurrenceType(Recurrence::TYPE_DAILY);
        $recurrenceNotInRangeWithException->setInterval(1);
        $recurrenceNotInRangeWithException->setOccurrences(2);
        $recurrenceNotInRangeWithException->setStartTime($eventNotInRangeWithExceptionInRangeStart);
        $eventNotInRangeWithExceptionInRange->setRecurrence($recurrenceNotInRangeWithException);
        $exceptionInRange = new CalendarEvent();
        $exceptionInRange->setCalendar($calendar);
        $exceptionInRange->setTitle('Test recurring event exception in range');
        $exceptionInRange->setStart(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-4 day')))
        );
        $exceptionInRange->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-4 day + 1 hour')))
        );
        $exceptionInRange->setAllDay(false);
        $exceptionInRange->setOriginalStart($eventNotInRangeWithExceptionInRangeStart);
        $exceptionInRange->setRecurringEvent($eventNotInRangeWithExceptionInRange);
        $exceptionInRange->setIsCancelled(false);
        $eventNotInRangeWithExceptionInRange->addRecurringEventException($exceptionInRange);
        $manager->persist($eventNotInRangeWithExceptionInRange);

        // Test recurring event in start:end range with canceled exception.
        $eventInRangeWithCancelledException = new CalendarEvent();
        $eventInRangeWithCancelledException->setCalendar($calendar);
        $eventInRangeWithCancelledException->setTitle(
            'Test recurring event in start:end range with canceled exception'
        );
        $eventInRangeWithCancelledExceptionStart = new \DateTime(gmdate(DATE_RFC3339, strtotime('-2 day')));
        $eventInRangeWithCancelledException->setStart($eventInRangeWithCancelledExceptionStart);
        $eventInRangeWithCancelledException->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-2 day + 1 hour')))
        );
        $eventInRangeWithCancelledException->setAllDay(false);
        $recurrenceInRangeWithException = new Recurrence();
        $recurrenceInRangeWithException->setRecurrenceType(Recurrence::TYPE_DAILY);
        $recurrenceInRangeWithException->setInterval(1);
        $recurrenceInRangeWithException->setOccurrences(2);
        $recurrenceInRangeWithException->setStartTime($eventInRangeWithCancelledExceptionStart);
        $eventInRangeWithCancelledException->setRecurrence($recurrenceInRangeWithException);
        $cancelledException = new CalendarEvent();
        $cancelledException->setCalendar($calendar);
        $cancelledException->setTitle('Test cancelled recurring event exception in range');
        $cancelledException->setStart($eventInRangeWithCancelledExceptionStart);
        $cancelledException->setEnd(
            new \DateTime(gmdate(DATE_RFC3339, strtotime('-2 day + 1 hour')))
        );
        $cancelledException->setAllDay(false);
        $cancelledException->setOriginalStart($eventInRangeWithCancelledExceptionStart);
        $cancelledException->setRecurringEvent($eventInRangeWithCancelledException);
        $cancelledException->setIsCancelled(true);
        $eventInRangeWithCancelledException->addRecurringEventException($cancelledException);
        $manager->persist($eventInRangeWithCancelledException);
        $this->setReference('eventInRangeWithCancelledException', $eventInRangeWithCancelledException);

        $manager->flush();
    }
}
