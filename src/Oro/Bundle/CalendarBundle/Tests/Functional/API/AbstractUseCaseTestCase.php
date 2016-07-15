<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Doctrine\Common\Collections\Criteria;
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
        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData'], true);
    }

    /**
     * @param array $expectedCalendarEvents
     * @param array $actualCalendarEvents
     */
    protected function assertCalendarEvents(array $expectedCalendarEvents, array $actualCalendarEvents)
    {
        $this->assertCount(count($expectedCalendarEvents), $actualCalendarEvents, 'Calendar Events count mismatch');

        reset($actualCalendarEvents);
        foreach ($expectedCalendarEvents as $expectedEventData) {
            $actualEvent = current($actualCalendarEvents);

            if (isset($expectedEventData['attendees'])) {
                $expectedAttendeesData = $expectedEventData['attendees'];
                unset($expectedEventData['attendees']);

                $this->assertCount(
                    count($expectedAttendeesData),
                    $actualEvent['attendees'],
                    sprintf(
                        'Calendar Event Attendees count mismatch for calendar event.%s Expected: %s%s Actual: %s',
                        PHP_EOL,
                        json_encode($expectedEventData),
                        PHP_EOL,
                        json_encode($actualEvent)
                    )
                );

                reset($actualEvent['attendees']);
                foreach ($expectedAttendeesData as $expectedAttendeeData) {
                    $actualAttendee = current($actualEvent['attendees']);
                    $this->assertArraysPartiallyEqual(
                        $expectedAttendeeData,
                        $actualAttendee,
                        'Calendar Event Attendee'
                    );

                    next($actualEvent['attendees']);
                }
            }

            $this->assertArraysPartiallyEqual($expectedEventData, $actualEvent, 'Calendar Event');

            next($actualCalendarEvents);
        }
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param string $entityAlias
     */
    protected function assertArraysPartiallyEqual(array $expected, array $actual, $entityAlias)
    {
        foreach ($expected as $propertyName => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $actual[$propertyName],
                sprintf(
                    '%s Property "%s" actual value does not match expected value.%s' .
                    'Expected data: %s.%sActual Data: %s',
                    $entityAlias,
                    $propertyName,
                    PHP_EOL,
                    json_encode($expected),
                    PHP_EOL,
                    json_encode($actual)
                )
            );
        }
    }

    /**
     * @param array $expectedCalendarEventsData
     * @param int   $calendarId
     *
     * @return array
     */
    protected function changeExpectedDataCalendarId(array $expectedCalendarEventsData, $calendarId)
    {
        foreach ($expectedCalendarEventsData as &$expectedCalendarEventData) {
            $expectedCalendarEventData['calendar'] = $calendarId;
        }

        return $expectedCalendarEventsData;
    }

    /**
     * @param int $eventId
     * @param int $expectedCount
     */
    protected function assertCalendarEventAttendeesCount($eventId, $expectedCount)
    {
        /** we should clear doctrine cache to get real result */
        $this->getEntityManager()->clear();

        $calendarEvent = $this->getCalendarEventById($eventId);

        $this->assertCount($expectedCount, $calendarEvent->getAttendees()->toArray());
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
    protected function deleteEventViaAPI($calendarEventId)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $calendarEventId])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    /**
     * @return CalendarEvent[]
     */
    public function getRecurringCalendarEventsFromDB()
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->isNull('recurringEvent'));

        $calendarEvents = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->matching($criteria);

        return $calendarEvents->toArray();
    }

    /**
     * @return CalendarEvent[]
     */
    public function getCalendarEventExceptionsFromDB()
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->neq('recurringEvent', null));

        $calendarEvents = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->matching($criteria);

        return $calendarEvents->toArray();
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
