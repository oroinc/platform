<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Manager\CalendarManager;

class CalendarManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarPropertyProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider2;

    /** @var CalendarManager */
    protected $manager;

    protected function setUp()
    {
        $this->calendarPropertyProvider =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\CalendarPropertyProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->manager = new CalendarManager($this->calendarPropertyProvider);

        $this->provider1 = $this->getMock('Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface');
        $this->provider2 = $this->getMock('Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface');

        $this->manager->addProvider('provider1', $this->provider1);
        $this->manager->addProvider('provider2', $this->provider2);
    }

    public function testGetCalendarsEmpty()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 2;
        $connections    = [];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItems')
            ->with($calendarId)
            ->will($this->returnValue($connections));

        $this->provider1->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [])
            ->will($this->returnValue([]));
        $this->provider2->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [])
            ->will($this->returnValue([]));

        $result = $this->manager->getCalendars($organizationId, $userId, $calendarId);
        $this->assertSame([], $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCalendars()
    {
        $organizationId = 123;
        $userId         = 123;
        $calendarId     = 2;
        $connections    = [
            [
                'id'             => 1,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider1',
                'calendar'       => 1,
                'visible'        => true,
                'position'       => 2,
                'extra_field'    => null,
            ],
            [
                'id'             => 2,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider1',
                'calendar'       => 10,
                'visible'        => true,
                'position'       => 3,
                'extra_field'    => null,
            ],
            [
                'id'             => 2,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider2',
                'calendar'       => 2,
                'visible'        => false,
                'position'       => 1,
                'extra_field'    => 'opt2',
            ],
        ];
        $defaultValues = [
            'id'             => null,
            'targetCalendar' => null,
            'calendarAlias'  => null,
            'calendar'       => null,
            'visible'        => true,
            'position'       => 0,
            'extra_field'    => [$this, 'getExtraFieldDefaultValue'],
        ];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItems')
            ->with($calendarId)
            ->will($this->returnValue($connections));
        $this->calendarPropertyProvider->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue($defaultValues));

        $this->provider1->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [1, 10])
            ->will(
                $this->returnValue(
                    [
                        1 => [
                            'calendarName' => 'calendar1'
                        ],
                        10 => null
                    ]
                )
            );
        $this->provider2->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [2])
            ->will(
                $this->returnValue(
                    [
                        2 => [
                            'calendarName' => 'calendar2'
                        ],
                        3 => [
                            'calendarName' => 'calendar3'
                        ],
                    ]
                )
            );

        $result = $this->manager->getCalendars($organizationId, $userId, $calendarId);
        $this->assertEquals(
            [
                [
                    'id'             => null,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider2',
                    'calendar'       => 3,
                    'visible'        => true,
                    'position'       => 0,
                    'extra_field'    => 'def_opt',
                    'calendarName'   => 'calendar3',
                    'removable'      => true
                ],
                [
                    'id'             => 2,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider2',
                    'calendar'       => 2,
                    'visible'        => false,
                    'position'       => 1,
                    'extra_field'    => 'opt2',
                    'calendarName'   => 'calendar2',
                    'removable'      => true
                ],
                [
                    'id'             => 1,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider1',
                    'calendar'       => 1,
                    'visible'        => true,
                    'position'       => 2,
                    'extra_field'    => 'def_opt',
                    'calendarName'   => 'calendar1',
                    'removable'      => true
                ],
            ],
            $result
        );
    }

    /**
     * @param string $fieldName
     *
     * @return mixed|null
     */
    public function getExtraFieldDefaultValue($fieldName)
    {
        return 'def_opt';
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $subordinate    = true;
        $allConnections = [
            ['calendarAlias' => 'provider1', 'calendar' => 10, 'visible' => true],
            ['calendarAlias' => 'provider1', 'calendar' => 20, 'visible' => false],
            ['calendarAlias' => 'provider2', 'calendar' => 10, 'visible' => false],
        ];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItemsVisibility')
            ->with($calendarId, $subordinate)
            ->will($this->returnValue($allConnections));

        $this->provider1->expects($this->once())
            ->method('getCalendarEvents')
            ->with($organizationId, $userId, $calendarId, $start, $end, [10 => true, 20 => false])
            ->will(
                $this->returnValue(
                    [
                        [
                            'id'    => 1,
                            'title' => 'event1',
                            'notifiable' => true
                        ],
                        [
                            'id'        => 2,
                            'title'     => 'event2',
                            'removable' => false
                        ],
                    ]
                )
            );
        $this->provider2->expects($this->once())
            ->method('getCalendarEvents')
            ->with($organizationId, $userId, $calendarId, $start, $end, [10 => false])
            ->will(
                $this->returnValue(
                    [
                        [
                            'id'    => 1,
                            'title' => 'event3',
                        ],
                        [
                            'id'         => 3,
                            'title'      => 'event4',
                            'editable'   => false,
                            'removable'  => false,
                            'notifiable' => false
                        ],
                    ]
                )
            );

        $result = $this->manager->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $subordinate);
        $this->assertEquals(
            [
                [
                    'id'            => 1,
                    'title'         => 'event1',
                    'calendarAlias' => 'provider1',
                    'editable'      => true,
                    'removable'     => true,
                    'notifiable'    => true
                ],
                [
                    'id'            => 2,
                    'title'         => 'event2',
                    'calendarAlias' => 'provider1',
                    'editable'      => true,
                    'removable'     => false,
                    'notifiable'    => false
                ],
                [
                    'id'            => 1,
                    'title'         => 'event3',
                    'calendarAlias' => 'provider2',
                    'editable'      => true,
                    'removable'     => true,
                    'notifiable'    => false
                ],
                [
                    'id'            => 3,
                    'title'         => 'event4',
                    'calendarAlias' => 'provider2',
                    'editable'      => false,
                    'removable'     => false,
                    'notifiable'    => false
                ],
            ],
            $result
        );
    }
}
