<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\PublicCalendarProvider;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class PublicCalendarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var PublicCalendarProvider */
    protected $provider;

    protected function setUp()
    {
        $this->doctrineHelper          = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventNormalizer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\PublicCalendarEventNormalizer')
                ->disableOriginalConstructor()
                ->getMock();
        $this->calendarConfig    =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();
        $this->securityFacade          = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PublicCalendarProvider(
            $this->doctrineHelper,
            $this->calendarEventNormalizer,
            $this->calendarConfig,
            $this->securityFacade
        );
    }

    public function testGetCalendarDefaultValuesDisabled()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                10 => null
            ],
            $result
        );
    }

    public function testGetCalendarDefaultValuesCannotAddEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $calendar1->setName('Master');
        $calendar1->setBackgroundColor('#FF0000');

        $calendars = [$calendar1];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));

        $repo  = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getPublicCalendarsQueryBuilder')
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -80,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarDefaultValuesCanAddEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $calendar1->setName('Master');
        $calendar1->setBackgroundColor('#FF0000');

        $calendars = [$calendar1];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_event_management')
            ->will($this->returnValue(true));

        $repo  = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getPublicCalendarsQueryBuilder')
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -80,
                    'canAddEvent'     => true,
                    'canEditEvent'    => true,
                    'canDeleteEvent'  => true,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarEventsPublicNotSupport()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals([], $result);
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];
        $events         = [['id' => 1]];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
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
            ->method('getPublicEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('c.id NOT IN (:invisibleIds)')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('invisibleIds', [20])
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
