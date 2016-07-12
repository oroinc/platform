<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AddAttendeesToCalendarEventTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testAttendeesCanBeAddedToAlreadyExistedCalendarEvent()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $calendarEventId = $this->postCalendarEventWithoutEmptyAttendees();
        $em->clear();

        $user = $this->getReference('simple_user');

        $firstAttendee = [
            'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
            'email'       => $user->getEmail(),
            'status'      => null,
            'type'        => Attendee::TYPE_REQUIRED,
        ];
        $this->addAttendeesWithFullBody($calendarEventId, [$firstAttendee]);

        $secondAttendee = [
            'displayName' => 'Ext',
            'email'       => 'ext@example.com',
            'status'      => Attendee::STATUS_TENTATIVE,
            'type'        => Attendee::TYPE_ORGANIZER,
        ];
        $this->addOnlyAttendees($calendarEventId, [$firstAttendee, $secondAttendee]);
        $em->clear();

        $calendarEvent = $this->getCalendarEvent($calendarEventId);
        $actual = $calendarEvent['attendees'];

        foreach ($actual as &$actualAttendee) {
            unset($actualAttendee['createdAt']);
            unset($actualAttendee['updatedAt']);
        }

        $expectedAttendees = [
            [
                'displayName' => $firstAttendee['displayName'],
                'email' => $firstAttendee['email'],
                'status' => Attendee::STATUS_NONE,
                'userId' => $user->getId(),
                'type' => $firstAttendee['type']
            ],
            [
                'displayName' => $secondAttendee['displayName'],
                'email' => $secondAttendee['email'],
                'status' => $secondAttendee['status'],
                'userId' => null,
                'type' => $secondAttendee['type']
            ],
        ];

        $this->assertEquals($actual, $expectedAttendees, '', 0.0, 10, true);
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function postCalendarEventWithoutEmptyAttendees()
    {
        $request = [
            'calendar'        => 1,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-01-01T08:03:46+00:00',
            'end'             => '2016-01-01T08:27:00+00:00',
            'allDay'          => false,
            'backgroundColor' => '#FF0000',
            'attendees'       => [],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @param int   $calendarEventId
     * @param array $attendees
     *
     * @return array
     */
    public function addAttendeesWithFullBody($calendarEventId, array $attendees)
    {
        $request = [
            'calendar'        => 1,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-01-01T08:03:46+00:00',
            'end'             => '2016-01-01T08:27:00+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => $attendees,
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertTrue($result['notifiable']);

        return $result;
    }

    /**
     * @param int   $calendarEventId
     * @param array $attendees
     *
     * @return array
     */
    public function addOnlyAttendees($calendarEventId, array $attendees)
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            ['attendees' => $attendees]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }

    /**
     * @param int $calendarEventId
     *
     * @return array
     */
    public function getCalendarEvent($calendarEventId)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $calendarEventId])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);

        return $result;
    }
}
