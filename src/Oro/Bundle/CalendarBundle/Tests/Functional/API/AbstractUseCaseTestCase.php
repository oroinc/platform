<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class AbstractUseCaseTestCase extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param string        $start
     * @param string        $end
     * @param string        $originalEventStartAt
     *
     * @return CalendarEvent
     */
    protected function addRecurringEventExceptionForCalendarEvent(
        CalendarEvent $calendarEvent,
        $start,
        $end,
        $originalEventStartAt
    ) {
        $attendees = [];
        foreach ($calendarEvent->getAttendees() as $attendee) {
            $attendees[] = [
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'status'      => $attendee->getStatus()->getId(),
                'type'        => $attendee->getType()->getId()
            ];
        }

        $data = [
            'originalStart'    => $originalEventStartAt,
            'isCancelled'      => false,
            'title'            => $calendarEvent->getTitle(),
            'description'      => $calendarEvent->getDescription(),
            'allDay'           => $calendarEvent->getAllDay(),
            'calendar'         => $calendarEvent->getCalendar()->getId(),
            'start'            => $start,
            'end'              => $end,
            'recurringEventId' => $calendarEvent->getId(),
            'attendees'        => $attendees,
        ];
        $calendarEventId = $this->addCalendarEventViaAPI($data);

        $events = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['id' => $calendarEventId]);

        $this->assertCount(1, $events);

        /** @var CalendarEvent $event */
        $event = reset($events);

        $this->assertNotNull($event);
        $this->assertFalse($event->isCancelled());

        return $event;
    }

    /**
     * Create new event
     *
     * @return int
     */
    protected function addCalendarEventViaAPI($data)
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $data);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @param int   $calendarEventId
     * @param array $data Dataa for update
     *
     * @return array
     */
    protected function updateCalendarEventViaAPI($calendarEventId, $data)
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            $data
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }

    /**
     * @param int $calendarEventId
     *
     * @return array
     */
    protected function getCalendarEventViaAPI($calendarEventId)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $calendarEventId])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function getOrderedCalendarEventsViaAPI(array $request)
    {
        $result = $this->getAllCalendarEventsViaAPI($request);

        /**
         * For avoiding different element order in different DB`s
         */
        usort(
            $result,
            function (array $first, array $second) {
                if ($first['start'] == $second['start']) {
                    return 0;
                }

                return  date_create($second['start']) > date_create($first['start']) ? 1 : -1;
            }
        );

        return $result;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function getAllCalendarEventsViaAPI(array $request)
    {
        $url = $this->getUrl('oro_api_get_calendarevents', $request);
        $this->client->request('GET', $url);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }

    /**
     * @param int $calendarEventId
     */
    protected function deleteRecurringEventException($calendarEventId)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $calendarEventId])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    /**
     * @param int $id
     *
     * @return null|CalendarEvent
     */
    protected function getCalendarEventById($id)
    {
        $event = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        return $event;
    }

    /**
     * @param User          $attendeeMappedUser
     * @param CalendarEvent $parentEvent
     *
     * @return CalendarEvent|null
     */
    protected function getAttendeeCalendarEvent(User $attendeeMappedUser, CalendarEvent $parentEvent)
    {
        $calendar = $this->getUserCalendar($attendeeMappedUser);

        return $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['parent' => $parentEvent, 'calendar' => $calendar]);
    }

    /**
     * @param User $user
     *
     * @return Calendar|null
     */
    protected function getUserCalendar(User $user)
    {
        return $this->getEntityManager()
            ->getRepository('OroCalendarBundle:Calendar')
            ->getUserCalendarsQueryBuilder($user->getOrganization()->getId(), $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
