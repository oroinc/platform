<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarConnectionControllerTest extends WebTestCase
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
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-04T11:29:46+00:00',
            'subordinate' => false
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    public function testPostCalendarEvent()
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
                    'email'       => $adminUser->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
            ],
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => ['wednesday'],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => '2016-05-04T11:29:46+00:00',
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
    }

    /**
     * @depends testPostCalendarEvent
     */
    public function testGetsAfterPost()
    {
        $user      = $this->getReference('simple_user');
        $admin = $this->getAdminUser();

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->assertEquals(
            [
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'notifiable'       => false,
                'calendarAlias'    => 'user',
                'attendees'        => [
                    [
                        'displayName' => $user->getFullName(),
                        'fullName'    => $user->getFullName() . ' ',
                        'email'       => $user->getEmail(),
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $user->getId(),
                    ],
                    [
                        'displayName' => $admin->getFullName(),
                        'fullName'    => $admin->getFullName() . ' ',
                        'email'       => $admin->getEmail(),
                        'status'      => 'accepted',
                        'type'        => 'required',
                        'userId'      => $admin->getId(),
                    ]
                ],
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_WEEKLY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => ['wednesday'],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
            ],
            $this->extractInterestingResponseData($result[0])
        );
    }

    /**
     * @depends testGetsAfterPost
     */
    public function testAddConnection()
    {
        $user = $this->getReference('simple_user');

        $request = [
            'calendar'        => $this->findCalendar($user)->getId(),
            'calendarAlias'   => 'user',
            'targetCalendar'  => self::DEFAULT_USER_CALENDAR_ID,
            'visible'         => true,
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendar_connection'), $request);

        $this->getJsonResponseContent($this->client->getResponse(), 201);
    }

    /**
     * @depends testAddConnection
     */
    public function testGetsAfterAddConnection()
    {
        $user      = $this->getReference('simple_user');
        $admin = $this->getAdminUser();

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
        $this->assertNotEquals($result[0]['calendar'], $result[1]['calendar']);

        $expectedCalendarEvent = [
            'title'            => 'Test Event',
            'description'      => 'Test Description',
            'start'            => '2016-05-04T11:29:46+00:00',
            'end'              => '2016-05-04T11:29:46+00:00',
            'allDay'           => true,
            'notifiable'       => false,
            'calendarAlias'    => 'user',
            'attendees'        => [
                [
                    'displayName' => $user->getFullName(),
                    'fullName'    => $user->getFullName() . ' ',
                    'email'       => $user->getEmail(),
                    'status'      => 'none',
                    'type'        => 'required',
                    'userId'      => $user->getId(),
                ],
                [
                    'displayName' => $admin->getFullName(),
                    'fullName'    => $admin->getFullName() . ' ',
                    'email'       => $admin->getEmail(),
                    'status'      => 'accepted',
                    'type'        => 'required',
                    'userId'      => $admin->getId(),
                ]
            ],
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => ['wednesday'],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => '2016-05-04T11:29:46+00:00',
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
        ];

        $this->assertEquals(
            [
                $expectedCalendarEvent,
                $expectedCalendarEvent,
            ],
            [
                $this->extractInterestingResponseData($result[0]),
                $this->extractInterestingResponseData($result[1])
            ]
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
                'title'            => null,
                'description'      => null,
                'start'            => null,
                'end'              => null,
                'allDay'           => null,
                'attendees'        => null,
                'notifiable'       => null,
                'calendarAlias'    => null,
                'recurrence'       => null,
            ]
        );

        $attendees = $result['attendees'];
        usort(
            $attendees,
            function ($user1, $user2) {
                return strcmp($user1['displayName'], $user2['displayName']);
            }
        );

        foreach ($attendees as &$attendee) {
            unset(
                $attendee['createdAt'],
                $attendee['updatedAt']
            );
        }

        $result['attendees'] = $attendees;
        unset($result['recurrence']['id']);

        return $result;
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
     * @param User $user
     */
    public function findCalendar(User $user)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:Calendar')
            ->findOneByOwner($user);
    }
}
