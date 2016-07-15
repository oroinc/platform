<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RecurringEventWithAttendeesInExceptionTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
            'Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    public function testDeleteExceptionWithAttendees()
    {
        $manager = $this->getContainer()->get('doctrine');
        $calendarRepo = $manager->getRepository('OroCalendarBundle:Calendar');
        $calendarEventRepo = $manager->getRepository('OroCalendarBundle:CalendarEvent');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $calendar1 = $calendarRepo->findDefaultCalendar($this->getReference('simple_user_1'), $organization->getId());

        // create recurring event
        $parameters = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => gmdate(DATE_RFC3339, strtotime('2016-07-02T09:00:00+00:00')),
            'end' => gmdate(DATE_RFC3339, strtotime('2016-07-02T09:30:00+00:00')),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $calendar1->getId(),
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [Recurrence::DAY_SATURDAY],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => gmdate(DATE_RFC3339, strtotime('2016-07-02T09:00:00+00:00')),
                'endTime' => null,
                'occurrences' => 5,
                'timeZone' => 'UTC'
            ],
            'attendees' => null,
        ];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            $parameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $recurringEventId = $result['id'];

        // create exception event
        $parameters = [
            'recurringEventId' => $result['id'],
            'title' => 'Test Recurring Event Exception',
            'description' => 'Test Recurring Exception Description',
            'start' => gmdate(DATE_RFC3339, strtotime('2016-07-09T16:00:00+00:00')),
            'end' => gmdate(DATE_RFC3339, strtotime('2016-07-09T16:30:00+00:00')),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $calendar1->getId(),
            'originalStart' => gmdate(DATE_RFC3339, strtotime('2016-07-09T09:00:00+00:00')),
            'isCancelled' => false,
            'attendees' => [
                [
                    'email' => 'simple_user_2@example.com',
                ],
                [
                    'email' => 'simple_user_3@example.com',
                ],
            ],
        ];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            $parameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $exceptionId = $result['id'];

        // checks expanded result (is used on UI) for each user
        $start = gmdate(DATE_RFC3339, strtotime('2016-06-01T00:00:00+00:00'));
        $end = gmdate(DATE_RFC3339, strtotime('2016-08-01T00:00:00+00:00'));
        // for simple_user_1
        $request = array(
            'calendar' => $calendar1->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // recurring event is expanded into five occurrences
        $this->assertCount(5, $result);
        // but in database there are still two records: recurring event and its exception
        $this->assertCount(2, $calendarEventRepo->findBy([
            'calendar' => $calendar1->getId()
        ]));

        // for simple_user_2
        $calendar2 = $calendarRepo->findDefaultCalendar($this->getReference('simple_user_2'), $organization->getId());
        $request = array(
            'calendar' => $calendar2->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // simple_user_2 sees only exception
        $this->assertCount(1, $result);
        $this->assertCount(1, $calendarEventRepo->findBy([
            'calendar' => $calendar2->getId()
        ]));

        // for simple_user_3
        $calendar3 = $calendarRepo->findDefaultCalendar($this->getReference('simple_user_3'), $organization->getId());
        $request = array(
            'calendar' => $calendar3->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // simple_user_3 sees only exception
        $this->assertCount(1, $result);
        $this->assertCount(1, $calendarEventRepo->findBy([
            'calendar' => $calendar3->getId()
        ]));

        // makes exception cancelled
        $parameters = [
            'isCancelled' => true,
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $exceptionId]),
            $parameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);
        $calendarEventRepo->clear();
        $exception = $calendarEventRepo->findOneBy(['id' => $exceptionId]);
        $this->assertTrue($exception->isCancelled());
        // there are one cancelled exception is in database
        $this->assertCount(1, $calendarEventRepo->findBy([
            'cancelled' => true,
        ]));

        // checks expanded result (is used on UI) for each user
        // for simple_user_1, events for simple_user_2, simple_user_3 should be deleted as child events
        $request = array(
            'calendar' => $calendar1->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // recurring event is expanded into four occurrences, one occurrence is cancelled
        $this->assertCount(4, $result);

        // delete exception
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $exceptionId])
        );
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $calendarEventRepo->clear();
        $exception = $calendarEventRepo->findOneBy(['id' => $exceptionId]);
        $this->assertEmpty($exception);
        // there are no exceptions in database
        $this->assertCount(0, $calendarEventRepo->findBy([
            'recurringEvent' => $recurringEventId,
        ]));

        // checks expanded result (is used on UI) for each user
        // for simple_user_1
        $request = array(
            'calendar' => $calendar1->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // recurring event is expanded into five occurrences
        $this->assertCount(5, $result);
        // for simple_user_2
        $request = array(
            'calendar' => $calendar2->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(0, $result);

        // for simple_user_3
        $request = array(
            'calendar' => $calendar3->getId(),
            'start' => $start,
            'end' => $end,
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(0, $result);
    }
}
