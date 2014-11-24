<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarProvider;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

class UserCalendarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nameFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventNormalizer;

    /** @var UserCalendarProvider */
    protected $provider;

    protected function setUp()
    {
        $this->doctrineHelper          = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameFormatter           = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventNormalizer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer')
                ->disableOriginalConstructor()
                ->getMock();

        $this->provider = new UserCalendarProvider(
            $this->doctrineHelper,
            $this->nameFormatter,
            $this->calendarEventNormalizer
        );
    }

    public function testGetCalendarDefaultValues()
    {
        $userId      = 123;
        $calendarId  = 2;
        $calendarIds = [1, 2];

        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, 1);
        $user1 = new User();
        $calendar1->setOwner($user1);

        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar2, 2);
        $user2 = new User();
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

        $this->nameFormatter->expects($this->at(0))
            ->method('format')
            ->with($this->identicalTo($user1))
            ->will($this->returnValue('John Doo'));
        $this->nameFormatter->expects($this->at(1))
            ->method('format')
            ->with($this->identicalTo($user2))
            ->will($this->returnValue('John Smith'));

        $result = $this->provider->getCalendarDefaultValues($userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                1 => [
                    'calendarName' => 'John Doo'
                ],
                2 => [
                    'calendarName' => 'John Smith',
                    'removable'    => false
                ]
            ],
            $result
        );
    }

    public function testGetCalendarEvents()
    {
        $calendarId  = 123;
        $userId      = 123;
        $start       = new \DateTime();
        $end         = new \DateTime();
        $subordinate = true;
        $events      = [['id' => 1]];

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
            ->with($calendarId, $this->identicalTo($start), $this->identicalTo($end), $subordinate)
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->will($this->returnValue($events));

        $result = $this->provider->getCalendarEvents($userId, $calendarId, $start, $end, $subordinate);
        $this->assertEquals($events, $result);
    }
}
