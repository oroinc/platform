<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;

class UserCalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reminderManager;

    /** @var UserCalendarEventNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->reminderManager = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new UserCalendarEventNormalizer(
            $this->securityFacade,
            $this->reminderManager
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents($events, $eventIds, $expected)
    {
        $calendarId = 123;
        $query      = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        if (!empty($events)) {
            $this->securityFacade->expects($this->exactly(2))
                ->method('isGranted')
                ->will(
                    $this->returnValueMap(
                        [
                            ['oro_calendar_event_update', null, true],
                            ['oro_calendar_event_delete', null, true],
                        ]
                    )
                );
        }

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            [
                'events'    => [],
                'eventIds'  => [],
                'expected'  => []
            ],
            [
                'events'    => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'eventIds'  => [1],
                'expected'  => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'editable'  => true,
                        'removable' => true
                    ],
                ]
            ],
            [
                'events'    => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'eventIds'  => [1],
                'expected'  => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'editable'  => true,
                        'removable' => true
                    ],
                ]
            ],
        ];
    }
}
