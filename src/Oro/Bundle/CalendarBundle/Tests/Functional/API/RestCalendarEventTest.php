<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Entity\Attendee;

/**
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    public function testGets()
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "start" => date(DATE_RFC3339, strtotime('-1 day')),
            "end" => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false
        );

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPost()
    {
        $request = array(
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => "Test Event",
            'description'     => "Test Description",
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'invitedUsers'    => [
                [
                    'displayName' => 'Admin',
                    'email'       => 'admin@example.com',
                    'origin'      => 'client',
                    'status'      => null,
                ],
            ]
        );
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

        /**
     * @depends testPost
     *
     * @param int $id
     */
    public function testGetAfterPost($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['invitedUsers'][0]['createdAt'],
            $result['invitedUsers'][0]['updatedAt'],
            $result['invitedUsers'][1]['createdAt'],
            $result['invitedUsers'][1]['updatedAt']
        );
        $this->assertEquals(
            [
                'id'              => $id,
                'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
                'title'           => 'Test Event',
                'description'     => 'Test Description',
                'start'           => '2016-05-04T11:29:46+00:00',
                'end'             => '2016-05-04T11:29:46+00:00',
                'allDay'          => true,
                'backgroundColor' => '#FF0000',
                'invitationStatus' => null,
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'invitedUsers'       => [
                    [
                        'displayName' => 'Admin',
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => null,
                        'type'        => null,
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(1, $attendees);

        $attendee = $attendees->first();
        $this->assertEquals('admin@example.com', $attendee->getEmail());
        $this->assertEquals('admin', $attendee->getUser()->getUsername());
        $this->assertEquals($attendee, $calendarEvent->getRelatedAttendee());
    }

    /**
     * @depends testPost
     *
     * @param int $id
     *
     * @return int
     */
    public function testPut($id)
    {
        $request = array(
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'origin'          => 'client',
            'invitedUsers'    => [
                [
                    'displayName' => 'Admin',
                    'email'       => 'admin@example.com',
                    'origin'      => 'client',
                ],
                [
                    'displayName' => 'Ext',
                    'email'       => 'ext@example.com',
                    'origin'      => 'external',
                    'status'      => 'tentative',
                    'type'        => 'organizer',
                ]
            ],
        );
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', array("id" => $id)),
            $request
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        return $id;
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGetAfterPut($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['invitedUsers'][0]['createdAt'],
            $result['invitedUsers'][0]['updatedAt'],
            $result['invitedUsers'][1]['createdAt'],
            $result['invitedUsers'][1]['updatedAt']
        );
        $this->assertEquals(
            [
                'id'              => $id,
                'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
                'title'           => 'Test Event Updated',
                'description'     => 'Test Description Updated',
                'start'           => '2016-05-04T11:29:46+00:00',
                'end'             => '2016-05-04T11:29:46+00:00',
                'allDay'          => true,
                'backgroundColor' => '#FF0000',
                'invitationStatus' => null,
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'invitedUsers'       => [
                    [
                        'displayName' => 'Admin',
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => null,
                        'type'        => null,
                    ],
                    [
                        'displayName' => 'Ext',
                        'email'       => 'ext@example.com',
                        'origin'      => 'external',
                        'status'      => 'tentative',
                        'type'        => 'organizer'
                    ]
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        $boundAttendees = array_values(array_filter(array_map(
            function (Attendee $attendee) {
                return $attendee->getUser() ? $attendee : null;
            },
            $attendees->toArray()
        )));
        
        $this->assertCount(1, $boundAttendees);
        $this->assertEquals('admin@example.com', $boundAttendees[0]->getEmail());
        $this->assertEquals('admin', $boundAttendees[0]->getUser()->getUsername());
        $this->assertEquals($boundAttendees[0], $calendarEvent->getRelatedAttendee());
    }

    /**
     * @depends testPut
     */
    public function testGetCreatedEvents()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => '2016-05-03T11:29:46+00:00',
            'end'   => '2016-05-05T11:29:46+00:00',
            'subordinate' => false
        );

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result);
        unset(
            $result[0]['id'],
            $result[0]['invitedUsers'][0]['createdAt'],
            $result[0]['invitedUsers'][0]['updatedAt'],
            $result[0]['invitedUsers'][1]['createdAt'],
            $result[0]['invitedUsers'][1]['updatedAt']
        );

        $this->assertEquals(
            [
                [
                    'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
                    'title'           => 'Test Event Updated',
                    'description'     => 'Test Description Updated',
                    'start'           => '2016-05-04T11:29:46+00:00',
                    'end'             => '2016-05-04T11:29:46+00:00',
                    'allDay'          => true,
                    'backgroundColor' => '#FF0000',
                    'invitationStatus' => null,
                    'parentEventId'    => null,
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false,
                    'calendarAlias'    => 'user',
                    'invitedUsers'       => [
                        [
                            'displayName' => 'Admin',
                            'email'       => 'admin@example.com',
                            'origin'      => 'client',
                            'status'      => null,
                            'type'        => null,
                        ],
                        [
                            'displayName' => 'Ext',
                            'email'       => 'ext@example.com',
                            'origin'      => 'external',
                            'status'      => 'tentative',
                            'type'        => 'organizer',
                        ]
                    ],
                ],
            ],
            [$this->extractInterestingResponseData($result[0])]
        );
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGetByCalendar($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_calendarevent_by_calendar',
                ['id' => self::DEFAULT_USER_CALENDAR_ID, 'eventId' => $id]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testCget($id)
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result[0]['id']);
    }

    /**
     * @depends testPut
     */
    public function testCgetFiltering()
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 10,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2050-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
    }

    public function extractInterestingResponseData(array $responseData)
    {
        $result = array_intersect_key(
            $responseData,
            [
                'id'              => null,
                'calendar'        => null,
                'title'           => null,
                'description'     => null,
                'start'           => null,
                'end'             => null,
                'allDay'          => null,
                'backgroundColor' => null,
                'invitationStatus' => null,
                'parentEventId'    => null,
                'invitedUsers'     => null,
                'editable'         => null,
                'removable'        => null,
                'notifiable'       => null,
                'calendarAlias'    => null,
            ]
        );

        $invitedUsers = $result['invitedUsers'];
        usort(
            $invitedUsers,
            function ($user1, $user2) {
                return strcmp($user1['displayName'], $user2['displayName']);
            }
        );
        $result['invitedUsers'] = $invitedUsers;

        return $result;
    }
}
