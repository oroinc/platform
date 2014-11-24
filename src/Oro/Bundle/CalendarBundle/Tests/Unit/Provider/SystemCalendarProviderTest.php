<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarProvider;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemCalendarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var UserCalendarProvider */
    protected $provider;

    protected function setUp()
    {
        $this->doctrineHelper          = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventNormalizer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarEventNormalizer')
                ->disableOriginalConstructor()
                ->getMock();
        $this->aclHelper               = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SystemCalendarProvider(
            $this->doctrineHelper,
            $this->calendarEventNormalizer,
            $this->aclHelper
        );
    }

    public function testGetCalendarDefaultValues()
    {
        $userId      = 1;
        $calendarId  = 1;
        $calendarIds = [1, 2];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $organization1 = new Organization();
        $calendar1->setOrganization($organization1);
        $calendar1->setName('Main OroCRM');

        $calendar2 = new SystemCalendar();
        ReflectionUtil::setId($calendar2, 2);
        $calendar2->setOrganization($organization1);
        $calendar2->setName('Second OroCRM');

        $calendars = [$calendar1, $calendar2];

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getSystemCalendarsByIdsQueryBuilder')
            ->with($calendarIds)
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

        $result = $this->provider->getCalendarDefaultValues($userId, $calendarId, $calendarIds);

        $this->assertEquals(
            [
                1 => [
                    'calendarName'  => 'Main OroCRM',
                    'position'      => -60,
                ],
                2 => [
                    'calendarName' => 'Second OroCRM',
                    'position'      => -60,
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
        $filters     = [];

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
            ->method('getSystemEventListByTimeIntervalQueryBuilder')
            ->with($calendarId, $this->identicalTo($start), $this->identicalTo($end), $filters)
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
