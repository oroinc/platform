<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;

/**
 * @dbIsolation
 */
class AddAttendeesToCalendarEventTest extends AbstractUseCaseTestCase
{
    public function testAttendeesCanBeAddedToAlreadyExistedCalendarEvent()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $calendarEventId = $this->postCalendarEventWithEmptyAttendees();
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

        $calendarEvent = $this->getCalendarEventViaAPI($calendarEventId);
        $this->assertNotEmpty($calendarEvent);
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
    public function postCalendarEventWithEmptyAttendees()
    {
        return $this->addCalendarEventViaAPI([
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-01-01T08:03:46+00:00',
            'end'             => '2016-01-01T08:27:00+00:00',
            'allDay'          => false,
            'backgroundColor' => '#FF0000',
            'attendees'       => [],
        ]);
    }

    /**
     * @param int   $calendarEventId
     * @param array $attendees
     *
     * @return array
     */
    public function addAttendeesWithFullBody($calendarEventId, array $attendees)
    {
        $result = $this->updateCalendarEventViaAPI(
            $calendarEventId,
            [
                'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
                'title'           => 'Test Event',
                'description'     => 'Test Description',
                'start'           => '2016-01-01T08:03:46+00:00',
                'end'             => '2016-01-01T08:27:00+00:00',
                'allDay'          => true,
                'backgroundColor' => '#FF0000',
                'attendees'       => $attendees,
            ]
        );
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
        return $this->updateCalendarEventViaAPI($calendarEventId, ['attendees' => $attendees]);
    }
}
