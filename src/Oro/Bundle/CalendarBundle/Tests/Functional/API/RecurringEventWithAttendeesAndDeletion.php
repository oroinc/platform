<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurringEventWithAttendeesAndDeletion extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testRecurringEventWithAttendeesAndDeletion()
    {
        $start = new \DateTime('2016-07-13 18:24:13');
        $end   = clone $start;
        $end->modify('+1 hour');

        $this->checkPreconditions($start, $end);

        $recurringCalendarEvent = $this->postRecurringEvent($start, $end);

        $allEvents = $this->getAllEvents($start, $end);

        $this->assertFalse($allEvents[0]['isCancelled']);

        $this->checkAfterPostConditions($start, $end, 5, 2);

        $this->postExclusionCalendarEvent($recurringCalendarEvent);

        $this->checkAfterPostConditions($start, $end, 6, 4);

        $allEvents = $this->getAllEvents($start, $end);

        $result = array_filter(
            $allEvents,
            function (array $element) {
                return $element['isCancelled'] === true;
            }
        );

        $this->assertCount(1, $result);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    protected function getAllEvents(\DateTime $start, \DateTime $end)
    {
        $newEnd = clone $end;
        $newEnd->modify('+2 month');

        $newStart = clone $start;
        $newStart->modify('-2 month');


        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => $newStart->format(DATE_RFC3339),
            'end'         => $newEnd->format(DATE_RFC3339),
            'subordinate' => true,
        ];

        return $this->getAllAPI($request);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    protected function checkPreconditions(\DateTime $start, \DateTime $end)
    {
        $result = $this->getAllEvents($start, $end);

        $this->assertEmpty($result);
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function getAllAPI(array $request)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return CalendarEvent
     */
    protected function postRecurringEvent(\DateTime $start, \DateTime $end)
    {
        $user = $this->getReference('simple_user');

        $newEnd = clone $end;
        $newEnd->modify('+1 month');

        $newStart = clone $start;
        $newStart->modify('-1 day');

        $request = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => $start->format(DATE_RFC3339),
            'end'         => $end->format(DATE_RFC3339),
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => $newStart->format(DATE_RFC3339),
                'occurrences'    => 5,
                'endTime'        => $newEnd->format(DATE_RFC3339),
            ],
            'attendees'   => [
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => 'admin@example.com',
                    'email'       => 'admin@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ],
        ];

        return $this->postAPI($request);
    }

    /**
     * @param CalendarEvent $recurringCalendarEvent
     *
     * @return CalendarEvent
     */
    protected function postExclusionCalendarEvent(CalendarEvent $recurringCalendarEvent)
    {
        $request = [
            'originalStart'    => $recurringCalendarEvent->getStart()->format(DATE_RFC3339),
            'isCancelled'      => true,
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'recurringEventId' => $recurringCalendarEvent->getId(),
            'start'            => date(DATE_RFC3339, strtotime('+4 hour')),
            'end'              => date(DATE_RFC3339, strtotime('+4 hour')),
            'title'            => $recurringCalendarEvent->getTitle(),
            'description'      => $recurringCalendarEvent->getDescription(),
        ];

        $this->postAPI($request);
    }

    /**
     * @param array $request
     *
     * @return CalendarEvent
     */
    protected function postAPI(array $request)
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

        return $event;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $postCount
     * @param int       $dbCount
     */
    protected function checkAfterPostConditions(\DateTime $start, \DateTime $end, $postCount, $dbCount)
    {
        $allEvents = $this->getAllEvents($start, $end);
        $this->assertCount($postCount, $allEvents);

        $allEvents = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findAll();

        $this->assertCount($dbCount, $allEvents);
    }
}
