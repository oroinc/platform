<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RecurringEventWithAttendeesAndDeletion extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    const EVENT_START_TIME = '2016-07-02T08:00:00P';
    const EVENT_END_TIME = '2016-07-02T08:30:00P';

    const RECURRENCE_START_TIME = '2016-07-01T00:00:00P';
    const RECURRENCE_END_TIME = '2016-07-30T00:00:00P';

    const SEARCH_START_TIME = '2016-06-26T00:00:00P';
    const SEARCH_END_TIME = '2016-08-07T00:00:00P';

    const EXCLUSION_START_TIME = '2016-07-02T10:00:00P';
    const EXCLUSION_END_TIME = '2016-07-02T10:30:00P';

    const ORIGINAL_START_TIME = '2016-07-09T08:00:00P';

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
        $this->checkPreconditions();

        $recurringCalendarEvent = $this->postRecurringEvent();

        $allEvents = $this->getAllEvents();

        $this->assertFalse($allEvents[0]['isCancelled']);

        $this->checkUIResponseEventQuantity(5);
        $this->checkEventQuantityInDB(2);

        $this->postExclusionCalendarEvent($recurringCalendarEvent);

        $this->checkUIResponseEventQuantity(4);
        $this->checkEventQuantityInDB(4);

        $this->checkCancelledEventsOnUIResponse(0);
        $this->checkCancelledEventsInDB(2);
    }

    /**
     * @return array
     */
    protected function getAllEvents()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => self::SEARCH_START_TIME,
            'end'         => self::SEARCH_END_TIME,
            'subordinate' => true,
        ];

        return $this->getAllAPI($request);
    }

    /**
     * @return array
     */
    protected function checkPreconditions()
    {
        $result = $this->getAllEvents();

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
     * @return CalendarEvent
     */
    protected function postRecurringEvent()
    {
        $request = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => self::EVENT_START_TIME,
            'end'         => self::EVENT_START_TIME,
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => self::RECURRENCE_START_TIME,
                'occurrences'    => 5,
                'endTime'        => self::RECURRENCE_END_TIME,
            ],
            'attendees'   => $this->getAttendees(),
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
            'originalStart'    => self::ORIGINAL_START_TIME,
            'isCancelled'      => true,
            'title'            => $recurringCalendarEvent->getTitle(),
            'description'      => $recurringCalendarEvent->getDescription(),
            'allDay'           => false,
            'attendees'        => $this->getAttendees(),
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'start'            => self::EXCLUSION_START_TIME,
            'end'              => self::EXCLUSION_END_TIME,
            'recurringEventId' => $recurringCalendarEvent->getId(),
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
     * @param int $number
     */
    protected function checkUIResponseEventQuantity($number)
    {
        $allEvents = $this->getAllEvents();
        $this->assertCount($number, $allEvents);
    }

    /**
     * @param int $number
     */
    protected function checkEventQuantityInDB($number)
    {
        $allEvents = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findAll();

        $this->assertCount($number, $allEvents);
    }

    /**
     * @return array
     */
    protected function getAttendees()
    {
        $user = $this->getReference('simple_user');

        return [
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
        ];
    }

    /**
     * @param int $number
     */
    protected function checkCancelledEventsOnUIResponse($number)
    {
        $allEvents = $this->getAllEvents();

        $result = array_filter(
            $allEvents,
            function (array $element) {
                return $element['isCancelled'] === true;
            }
        );

        $this->assertCount($number, $result);
    }

    /**
     * @param int $number
     */
    protected function checkCancelledEventsInDB($number)
    {
        $allEvents = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['cancelled' => true]);

        $this->assertCount($number, $allEvents);
    }
}
