<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\CalendarEventNormalizer;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;

class CalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var CalendarEventNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->doctrine       = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new CalendarEventNormalizer($this->doctrine, $this->securityFacade);
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents($events, $eventIds, $reminders, $expected)
    {
        $calendarId = 123;
        $qb         = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query      = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        $reminderRepo = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroReminderBundle:Reminder')
            ->will($this->returnValue($reminderRepo));
        $reminderRepo->expects($this->once())
            ->method('findRemindersByEntities')
            ->with($eventIds, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->will($this->returnValue($reminders));

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

        $result = $this->normalizer->getCalendarEvents($calendarId, $qb);
        $this->assertEquals($expected, $result);
    }

    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));
        $reminder  = new Reminder();
        $reminder
            ->setRelatedEntityId(1)
            ->setMethod('email')
            ->setInterval(new ReminderInterval(10, ReminderInterval::UNIT_MINUTE));

        return [
            [
                'events'    => [],
                'eventIds'  => [],
                'reminders' => [],
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
                'reminders' => [],
                'expected'  => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'editable'  => true,
                        'removable' => true,
                        'reminders' => []
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
                'reminders' => [$reminder],
                'expected'  => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'editable'  => true,
                        'removable' => true,
                        'reminders' => [
                            [
                                'method'   => $reminder->getMethod(),
                                'interval' => [
                                    'number' => $reminder->getInterval()->getNumber(),
                                    'unit'   => $reminder->getInterval()->getUnit(),
                                ]
                            ]
                        ]
                    ],
                ]
            ],
        ];
    }
}
