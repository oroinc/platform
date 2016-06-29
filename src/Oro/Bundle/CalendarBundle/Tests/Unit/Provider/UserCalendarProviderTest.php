<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarProvider;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

class UserCalendarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $recurrenceModel;

    /** @var UserCalendarProvider */
    protected $provider;

    protected function setUp()
    {
        $this->doctrineHelper          = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityNameResolver      = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventNormalizer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer')
                ->disableOriginalConstructor()
                ->getMock();
        $this->recurrenceModel =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence')
                ->disableOriginalConstructor()
                ->getMock();

        $this->provider = new UserCalendarProvider(
            $this->doctrineHelper,
            $this->entityNameResolver,
            $this->calendarEventNormalizer,
            $this->recurrenceModel
        );
    }

    public function testGetCalendarDefaultValues()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 20;
        $calendarIds    = [10, 20];

        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, $calendarIds[0]);
        $user1 = new User();
        ReflectionUtil::setId($user1, $userId);
        $calendar1->setOwner($user1);

        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar2, $calendarIds[1]);
        $user2 = new User();
        ReflectionUtil::setId($user2, 456);
        $calendar2->setOwner($user2);

        $calendars = [$calendar1, $calendar2];

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $qb->expects($this->once())
            ->method('select')
            ->with('o, owner')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('o.owner', 'owner')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('expr')
            ->will($this->returnValue(new Expr()));
        $qb->expects($this->once())
            ->method('where')
            ->with(new Expr\Func('o.id IN', $calendarIds))
            ->will($this->returnSelf());

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->entityNameResolver->expects($this->at(0))
            ->method('getName')
            ->with($this->identicalTo($user1))
            ->will($this->returnValue('John Doo'));
        $this->entityNameResolver->expects($this->at(1))
            ->method('getName')
            ->with($this->identicalTo($user2))
            ->will($this->returnValue('John Smith'));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendarIds[0] => [
                    'calendarName' => 'John Doo',
                    'userId'       => $userId,
                ],
                $calendarIds[1] => [
                    'calendarName'   => 'John Smith',
                    'userId'         => 456,
                    'removable'      => false,
                    'canAddEvent'    => true,
                    'canEditEvent'   => true,
                    'canDeleteEvent' => true,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];
        $events         = [
            [
                'id'    => 1,
                'start' => '2016-05-04T11:29:46+00:00',
                'end'   => '2016-05-06T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'calculatedEndTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
            ],
        ];
        $expectedEvents = [
            [
                'id'    => 1,
                'start' => '2016-05-04T11:29:46+00:00',
                'end'   => '2016-05-06T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'recurrencePattern' => null,
                'removable' => false,
                'startEditable' => false,
                'durationEditable' => false,
            ],
            [
                'id'    => 1,
                'start' => '2016-05-05T11:29:46+00:00',
                'end'   => '2016-05-07T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'recurrencePattern' => null,
                'removable' => false,
                'startEditable' => false,
                'durationEditable' => false,
            ],
        ];

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getUserEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('c.id IN (:visibleIds)')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->will($this->returnValue($events));
        $this->recurrenceModel->expects($this->once())
            ->method('getOccurrences')
            ->will($this->returnValue([
                new \DateTime('2016-05-04T11:29:46+00:00'),
                new \DateTime('2016-05-05T11:29:46+00:00'),
            ]));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals($expectedEvents, $result);
    }

    public function testGetCalendarEventsAllInvisible()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => false, 20 => false];
        $events         = [['id' => 1]];

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getUserEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->will($this->returnValue($events));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals($events, $result);
    }
}
