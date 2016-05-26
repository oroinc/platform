<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /** @var array */
    protected static $regularEventParameters;

    /** @var array */
    protected static $recurringEventParameters;

    /** @var array */
    protected static $recurringEventExceptionParameters;

    public static function setUpBeforeClass()
    {
        self::$regularEventParameters            = [
            'title'           => 'Test Regular Event',
            'description'     => 'Test Regular Event Description',
            'start'           => gmdate(DATE_RFC3339),
            'end'             => gmdate(DATE_RFC3339),
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
        ];
        self::$recurringEventParameters          = [
            'title'           => 'Test Recurring Event',
            'description'     => 'Test Recurring Event Description',
            'start'           => gmdate(DATE_RFC3339),
            'end'             => gmdate(DATE_RFC3339),
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'recurrence'      => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'instance'       => null,
                'dayOfWeek'      => [],
                'dayOfMonth'     => null,
                'monthOfYear'    => null,
                'startTime'      => gmdate(DATE_RFC3339),
                'endTime'        => null,
                'occurrences'    => null,
            ],
        ];
        self::$recurringEventExceptionParameters = [
            'title'            => 'Test Recurring Event Exception',
            'description'      => 'Test Recurring Exception Description',
            'start'            => gmdate(DATE_RFC3339),
            'end'              => gmdate(DATE_RFC3339),
            'allDay'           => true,
            'backgroundColor'  => '#FF0000',
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'recurringEventId' => -1, // is set dynamically
            'originalStart'    => gmdate(DATE_RFC3339),
            'isCancelled'      => true,
        ];
    }

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
                'Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData',
            ]
        );
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
     * Creates regular event.
     *
     * @return int
     */
    public function testPostRegularEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$regularEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

        return $result['id'];
    }

    /**
     * Reads regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testGetRegularEvent($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        foreach (self::$regularEventParameters as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testPutRegularEvent($id)
    {
        self::$regularEventParameters['title'] = 'Test Regular Event Updated';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            self::$regularEventParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);
        $this->assertEquals(self::$regularEventParameters['title'], $event->getTitle());
    }

    /**
     * Deletes regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testDeleteRegularEvent($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $id])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $id]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
    }

    /**
     * Creates recurring event.
     *
     * @return array
     */
    public function testPostRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);
        $this->assertEquals(
            Recurrence::MAX_END_DATE,
            $event->getRecurrence()->getCalculatedEndTime()->format(DATE_RFC3339)
        );

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * Reads recurring event.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     */
    public function testGetRecurringEvent($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);
    }

    /**
     * Creates regular event.
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
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

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
                        'type'        => 'optional',
                        'user_id'     => $user->getId(),
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => 'optional',
                        'user_id'     => $adminUser->getId(),
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
                ],
            ],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);
        $this->assertEquals(self::$regularEventParameters['title'], $event->getTitle());
    }

    /**
     * Deletes regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testGetAfterPut($id)
    {
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);

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
                        'user_id'     => null,
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => $adminUser->getEmail(),
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => 'optional',
                        'user_id'     => $adminUser->getId(),
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
                            'type'        => 'optional',
                        ],
                    ],
                ],
            ],
            [$this->extractInterestingResponseData($result[0])]
        );
    }

    /**
     * Updates recurring event. The goal is to test transformation from recurring event to regular one.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testPutRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result                                 = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event                                  = $this->getContainer()->get('doctrine')->getRepository(
            'OroCalendarBundle:CalendarEvent'
        )
            ->find($result['id']);
        $data['id']                             = $event->getId();
        $data['recurrenceId']                   = $event->getRecurrence()->getId();
        $recurringEventParameters               = self::$recurringEventParameters;
        $recurringEventParameters['recurrence'] = null; // recurring event will become regular event.
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]);
        $this->assertNull($event->getRecurrence());
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($event);
        $em->flush();
    }

    /**
     * Deletes recurring event.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     *
     * @TODO add test when recurring event with exception is deleted.
     */
    public function testDeleteRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result               = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event                = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id']           = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
    }

    /**
     * Creates recurring event exception.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     *
     * @return array
     */
    public function testPostRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            self::$recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $exception = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($exception);
        $this->assertEquals($data['id'], $exception->getRecurringEvent()->getId());

        return ['id' => $result['id'], 'recurringEventId' => $data['id']];
    }

    /**
     * Reads recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testGetRecurringEventException($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        foreach (self::$recurringEventExceptionParameters as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testPutRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['isCancelled'] = false;
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            self::$recurringEventExceptionParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($data['id']);
        $this->assertEquals(self::$recurringEventExceptionParameters['isCancelled'], $event->getIsCancelled());
    }

    /**
     * Deletes recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testDeleteRecurringEventException($data)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $registry       = $this->getContainer()->get('doctrine');
        $recurringEvent = $registry->getRepository('OroCalendarBundle:CalendarEvent')
            ->find(['id' => $data['recurringEventId']]);
        $registry->getManager()->remove($recurringEvent);
        $registry->getManager()->flush();
    }

    public function testCgetByDateRangeFilter()
    {
        /** @todo: FIX THIS! * */
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00', #gmdate(DATE_RFC3339, strtotime('-5 day')
            'end'         => '2016-05-05T11:29:46+00:00', #gmdate(DATE_RFC3339, strtotime('+5 day')),
            'subordinate' => true #'subordinate' => false
        ];
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(6, $result);
    }

    public function testCgetByPagination()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 100,
            'subordinate' => false,
        ];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(10, $result);
    }

    public function testCgetByPaginationWithRecurringEventIdFilter()
    {
        $request = array(
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'page'             => 1,
            'limit'            => 100,
            'subordinate'      => false,
            'recurringEventId' => $this->getReference('eventInRangeWithCancelledException')->getId(),
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
    }

    public function testGetByCalendar()
    {
        $id = $this->getReference('eventInRangeWithCancelledException')->getId();
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
            ],
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
                        'type'        => 'optional',
                        'user_id'     => $user->getId(),
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'origin'      => 'client',
                        'status'      => 'accepted',
                        'type'        => 'optional',
                        'user_id'     => $adminUser->getId(),
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
                        'user_id'     => $user->getId(),
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
