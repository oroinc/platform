<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithAttendeesTest extends WebTestCase
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
            'subordinate' => false,
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
    public function testPost()
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
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => 'attendee without email',
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => 'attendee with email and with unknown type',
                    'email'       => 'unknown-type@email.com',
                    'type'        => 'unknown_type',
                ],
                [
                    'displayName' => 'attendee with email and with type = null',
                    'email'       => 'type-null@email.com',
                    'type'        => null,
                ],
                [
                    'displayName' => 'attendee without email and with type = null',
                    'type'        => null,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @depends testPost
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

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

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
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => true,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => 'simple_user@example.com',
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $user->getId(),
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'status'      => 'accepted',
                        'type'        => Attendee::TYPE_ORGANIZER,
                        'userId'      => $adminUser->getId(),
                    ],
                    [
                        'displayName' => 'attendee with email and with type = null',
                        'email'       => 'type-null@email.com',
                        'type'        => null,
                        'userId'      => null,
                        'status'      => 'none',
                    ],
                    [
                        'displayName' => 'attendee with email and with unknown type',
                        'email'       => 'unknown-type@email.com',
                        'userId'      => null,
                        'status'      => 'none',
                        'type'        => null,
                    ],
                    [
                        'displayName' => 'attendee without email',
                        'email'       => null,
                        'userId'      => null,
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_OPTIONAL,
                    ],
                    [
                        'displayName' => 'attendee without email and with type = null',
                        'type'        => null,
                        'email'       => null,
                        'userId'      => null,
                        'status'      => 'none',
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(6, $attendees);

        $admin = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'admin@example.com';
            }
        )->first();
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals('admin', $admin->getUser()->getUsername());
        $this->assertEquals($admin, $calendarEvent->getRelatedAttendee());

        $simpleUser = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'simple_user@example.com';
            }
        )->first();
        $this->assertEquals('simple_user@example.com', $simpleUser->getEmail());
        $this->assertEquals('simple_user', $simpleUser->getUser()->getUsername());
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
        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                    'email'       => $adminUser->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => 'Ext',
                    'email'       => 'ext@example.com',
                    'status'      => 'tentative',
                    'type'        => 'organizer',
                ],
            ],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertTrue($result['notifiable']);

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

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

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
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => true,
                'attendees'        => [
                    [
                        'displayName' => 'Ext',
                        'email'       => 'ext@example.com',
                        'status'      => 'tentative',
                        'type'        => 'organizer',
                        'userId'      => null,
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => $adminUser->getEmail(),
                        'status'      => 'accepted',
                        'type'        => 'required',
                        'userId'      => $adminUser->getId(),
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

        $boundAttendees = array_values(
            array_filter(
                array_map(
                    function (Attendee $attendee) {
                        return $attendee->getUser() ? $attendee : null;
                    },
                    $attendees->toArray()
                )
            )
        );

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
            'subordinate' => false,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result);

        foreach ($result[0]['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

        unset($result[0]['id']);

        $adminUser = $this->getAdminUser();

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
                    'parentEventId'    => null,
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => true,
                    'calendarAlias'    => 'user',
                    'attendees'        => [
                        [
                            'displayName' => 'Ext',
                            'email'       => 'ext@example.com',
                            'status'      => 'tentative',
                            'type'        => 'organizer',
                            'fullName'    => '',
                            'userId'      => null,
                        ],
                        [
                            'displayName' => 'John Doe',
                            'email'       => 'admin@example.com',
                            'status'      => 'accepted',
                            'type'        => 'required',
                            'fullName'    => 'John Doe ',
                            'userId'      => $adminUser->getId(),
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
            'subordinate' => true,
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
            'subordinate' => false,
        ];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2050-03-04T20:00:00+0000')
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
            'invitedUsers'    => [$user->getId()],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends    testPostInvitedUsers
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

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

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
                'invitationStatus' => null,
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => true,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => $user->getEmail(),
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $user->getId(),
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPostRemoveAttendees()
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
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostRemoveAttendees
     *
     * @param int $id
     */
    public function testGetAfterPostRemoveAttendees($id)
    {
        $user      = $this->getReference('simple_user');
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

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
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => $user->getEmail(),
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_REQUIRED,
                        'userId'      => $user->getId(),
                    ],
                    [
                        'email'       => $adminUser->getEmail(),
                        'status'      => 'accepted',
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'type'        => Attendee::TYPE_ORGANIZER,
                        'userId'      => $adminUser->getId(),
                    ],
                ],
                'invitationStatus' => 'accepted',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => true,
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        $admin = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'admin@example.com';
            }
        )->first();
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals('admin', $admin->getUser()->getUsername());
        $this->assertEquals($admin, $calendarEvent->getRelatedAttendee());

        $simpleUser = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'simple_user@example.com';
            }
        )->first();
        $this->assertEquals('simple_user@example.com', $simpleUser->getEmail());
        $this->assertEquals('simple_user', $simpleUser->getUser()->getUsername());
    }

    /**
     * @depends testPostRemoveAttendees
     *
     * @param int $id
     *
     * @return int
     */
    public function testPutRemoveAttendees($id)
    {
        $request = [
            'attendees' => [],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertFalse($result['notifiable']);

        return $id;
    }

    /**
     * @depends testPutRemoveAttendees
     *
     * @param int $id
     */
    public function testGetAfterPutRemoveAttendees($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }

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
                'invitationStatus' => null,
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'notifiable'       => false,
                'attendees'        => [],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(0, $attendees);
    }

    public function testBindUserToAttendeeIsCaseInsensitive()
    {
        $this->getReference('simple_user')->setEmail('simple_uSer@example.com');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();

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
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => 'sImple_user@example.com',
                    'status'      => null,
                ],
            ]
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);

        $attendee = $calendarEvent->getAttendees()->first();
        $this->assertEquals($user->getId(), $attendee->getUser()->getId());
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPostWithNullAttendee()
    {
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
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostWithNullAttendee
     *
     * @param int $id
     *
     * @return int
     */
    public function testPutWithNullAttendee($id)
    {
        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T12:29:46+00:00',
            'end'             => '2016-05-04T12:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => '',
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
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

    /**
     * @param Attendee[]|Collection $attendees
     *
     * @return Attendee[]
     */
    protected function sortAttendees($attendees)
    {
        if ($attendees instanceof Collection) {
            $attendees = $attendees->toArray();
        }

        usort(
            $attendees,
            function (Attendee $attendee1, Attendee $attendee2) {
                return strcmp($attendee1->getDisplayName(), $attendee2->getDisplayName());
            }
        );

        return $attendees;
    }
}
