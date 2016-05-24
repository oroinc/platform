<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testGets()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPostOriginClient()
    {
        $user = $this->getReference('simple_user');

        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'origin'          => 'client',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                    'email'       => $adminUser->getEmail(),
                    'origin'      => 'client',
                    'status'      => null,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'origin'      => 'client',
                    'status'      => null,
                ],
            ]
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostOriginClient
     *
     * @param int $id
     */
    public function testGetAfterPost($id)
    {
        $user      = $this->getReference('simple_user');
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['attendees'][0]['createdAt'],
            $result['attendees'][0]['updatedAt'],
            $result['attendees'][1]['createdAt'],
            $result['attendees'][1]['updatedAt']
        );
        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'accepted',
                'origin'           => 'client',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => 'simple_user@example.com',
                        'origin'      => 'client',
                        'status'      => 'none',
                        'type'        => null,
                        'user_id'     => $user->getId()
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => null,
                        'user_id'     => $adminUser->getId()
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        $admin = $attendees->get(1);
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals('admin', $admin->getUser()->getUsername());
        $this->assertEquals($admin, $calendarEvent->getRelatedAttendee());

        $simpleUser = $attendees->first();
        $this->assertEquals('simple_user@example.com', $simpleUser->getEmail());
        $this->assertEquals('simple_user', $simpleUser->getUser()->getUsername());
    }

    /**
     * @depends testPostOriginClient
     *
     * @param int $id
     *
     * @return int
     */
    public function testPut($id)
    {
        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'origin'          => 'client',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                    'email'       => $adminUser->getEmail(),
                    'origin'      => 'client',
                    'status'      => null,
                ],
                [
                    'displayName' => 'Ext',
                    'email'       => 'ext@example.com',
                    'origin'      => 'external',
                    'status'      => 'tentative',
                    'type'        => 'organizer',
                ]
            ],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
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
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['attendees'][0]['createdAt'],
            $result['attendees'][0]['updatedAt'],
            $result['attendees'][1]['createdAt'],
            $result['attendees'][1]['updatedAt']
        );
        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event Updated',
                'description'      => 'Test Description Updated',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'accepted',
                'origin'           => 'client',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'attendees'        => [
                    [
                        'displayName' => 'Ext',
                        'email'       => 'ext@example.com',
                        'origin'      => 'external',
                        'status'      => 'tentative',
                        'type'        => 'organizer',
                        'user_id'     => null
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => $adminUser->getEmail(),
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => null,
                        'user_id'     => $adminUser->getId()
                    ],
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
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => false
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result);
        unset(
            $result[0]['id'],
            $result[0]['attendees'][0]['createdAt'],
            $result[0]['attendees'][0]['updatedAt'],
            $result[0]['attendees'][1]['createdAt'],
            $result[0]['attendees'][1]['updatedAt']
        );

        $this->assertEquals(
            [
                [
                    'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                    'title'            => 'Test Event Updated',
                    'description'      => 'Test Description Updated',
                    'start'            => '2016-05-04T11:29:46+00:00',
                    'end'              => '2016-05-04T11:29:46+00:00',
                    'allDay'           => true,
                    'backgroundColor'  => '#FF0000',
                    'invitationStatus' => 'accepted',
                    'origin'           => 'client',
                    'parentEventId'    => null,
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false,
                    'calendarAlias'    => 'user',
                    'attendees'        => [
                        [
                            'displayName' => 'Ext',
                            'email'       => 'ext@example.com',
                            'origin'      => 'external',
                            'status'      => 'tentative',
                            'type'        => 'organizer',
                        ],
                        [
                            'displayName' => 'John Doe',
                            'email'       => 'admin@example.com',
                            'origin'      => 'client',
                            'status'      => 'accepted',
                            'type'        => null,
                        ],
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
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        ];
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
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 10,
            'subordinate' => false
        ];
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

    /**
     * @depends testPut
     */
    public function testDelete($id)
    {
        // guard
        $this->assertNotNull(
            $this->getContainer()->get('doctrine')
                ->getRepository('OroCalendarBundle:CalendarEvent')
                ->find($id)
        );

        $this->client->request('DELETE', $this->getUrl('oro_api_get_calendarevent', ['id' => $id]));
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCalendarBundle:CalendarEvent')
            ->clear();

        $this->assertNull(
            $this->getContainer()->get('doctrine')
                ->getRepository('OroCalendarBundle:CalendarEvent')
                ->find($id)
        );
    }

    /**
     * @param array $responseData
     *
     * @return array
     */
    public function extractInterestingResponseData(array $responseData)
    {
        $result = array_intersect_key(
            $responseData,
            [
                'id'               => null,
                'calendar'         => null,
                'title'            => null,
                'description'      => null,
                'start'            => null,
                'end'              => null,
                'allDay'           => null,
                'backgroundColor'  => null,
                'invitationStatus' => null,
                'origin'           => null,
                'parentEventId'    => null,
                'attendees'        => null,
                'editable'         => null,
                'removable'        => null,
                'notifiable'       => null,
                'calendarAlias'    => null,
            ]
        );

        $attendees = $result['attendees'];
        usort(
            $attendees,
            function ($user1, $user2) {
                return strcmp($user1['displayName'], $user2['displayName']);
            }
        );
        $result['attendees'] = $attendees;

        return $result;
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPostOriginServer()
    {
        $user      = $this->getReference('simple_user');
        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => "Test Event",
            'description'     => "Test Description",
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'origin'          => 'server',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                    'email'       => $adminUser->getEmail(),
                    'origin'      => 'client',
                    'status'      => null,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'origin'      => 'client',
                    'status'      => null,
                ],
            ]
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostOriginServer
     *
     * @param int $id
     */
    public function testGetAfterPostOriginServer($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $user      = $this->getReference('simple_user');
        $adminUser = $this->getAdminUser();

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['attendees'][0]['createdAt'],
            $result['attendees'][0]['updatedAt'],
            $result['attendees'][1]['createdAt'],
            $result['attendees'][1]['updatedAt']
        );
        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'accepted',
                'origin'           => 'server',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => true,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => 'simple_user@example.com',
                        'origin'      => 'client',
                        'status'      => 'none',
                        'type'        => null,
                        'user_id'     => $user->getId()
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => null,
                        'user_id'     => $adminUser->getId()
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        foreach ($attendees as $attendee) {
            if ($attendee->getEmail() === 'admin@example.com') {
                $this->assertEquals($attendee, $calendarEvent->getRelatedAttendee());
            }
        }
    }


    /**
     * Create new event with invitedUsers field
     *
     * @return int
     *
     * @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees'
     */
    public function testPostInvitedUsers()
    {
        $user = $this->getReference('simple_user');

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'origin'          => 'server',
            'invitedUsers'    => [$user->getId()]
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostInvitedUsers
     *
     * @param int $id
     *
     * @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees'
     */
    public function testGetAfterPostInvitedUsers($id)
    {
        $user = $this->getReference('simple_user');

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        unset(
            $result['attendees'][0]['createdAt'],
            $result['attendees'][0]['updatedAt'],
            $result['attendees'][1]['createdAt'],
            $result['attendees'][1]['updatedAt']
        );

        $this->assertCount(1, $result['attendees']);
        $this->assertCount(1, $result['invitedUsers']);
        $this->assertEquals($user->getId(), $result['invitedUsers'][0]);

        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'none',
                'origin'           => 'client',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => $user->getEmail(),
                        'origin'      => 'client',
                        'status'      => 'none',
                        'type'        => 'optional',
                        'user_id'     => $user->getId()
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );
    }

    /**
     * @return User
     */
    protected function getAdminUser()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneByEmail('admin@example.com');
    }
}
