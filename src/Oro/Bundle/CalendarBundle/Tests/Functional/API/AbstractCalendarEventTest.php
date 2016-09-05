<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * @dbIsolation
 */
abstract class AbstractCalendarEventTest extends WebTestCase
{
    const DATE_RANGE_START = '-5 day';
    const DATE_RANGE_END = '+5 day';

    const DEFAULT_USER_CALENDAR_ID = 1;
    
    /** @var array */
    protected static $regularEventParameters;

    /** @var array */
    protected static $recurringEventParameters;

    /** @var array */
    protected static $recurringEventExceptionParameters;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData',
            'Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets',
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);

        $targetOne = $this->getReference('activity_target_one');
        self::$recurringEventParameters['contexts'] = json_encode([
            'entityId' => $targetOne->getId(),
            'entityClass' => get_class($targetOne),
        ]);
    }

    public static function setUpBeforeClass()
    {
        self::$regularEventParameters = [
            'title' => 'Test Regular Event',
            'description' => 'Test Regular Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
        ];
        self::$recurringEventParameters = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => gmdate(DATE_RFC3339),
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
            'attendees' => [
                [
                    'email' => 'simple_user@example.com',
                ],
            ],
        ];
        self::$recurringEventExceptionParameters = [
            'title' => 'Test Recurring Event Exception',
            'description' => 'Test Recurring Exception Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurringEventId' => -1, // is set dynamically
            'originalStart' => gmdate(DATE_RFC3339),
            'isCancelled' => true,
            'attendees' => [
                [
                    'email' => 'simple_user@example.com',
                ],
            ],
        ];
    }
}
