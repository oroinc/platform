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
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\CalendarEventNormalizer')
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
        $userId      = 123;
        $calendarId  = 2;
        $calendarIds = [1, 2];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $organization1 = new Organization();
        $organization1->setName('Envlights');
        $calendar1->setOwner($organization1);

        $calendar2 = new SystemCalendar();
        ReflectionUtil::setId($calendar2, 2);
        $organization2 = new Organization();
        $organization2->setName('OroCRM');
        $calendar2->setOwner($organization2);

        $calendars = [$calendar1, $calendar2];


        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $repo->expects($this->once())
            ->method('getCalendarsByIdsQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->will($this->returnValue($query));

        $result = $this->provider->getCalendarDefaultValues($userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                1 => [
                    'calendarName' => 'Envlights'
                ],
                2 => [
                    'calendarName' => 'OroCRM',
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
        $filters     = ['public' => false];
        $kind        = 'system';

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
            ->method('getEventListByTimeIntervalQueryBuilder')
            ->with($calendarId, $this->identicalTo($start), $this->identicalTo($end), $subordinate, $filters, $kind)
            ->will($this->returnValue($qb));

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($qb))
            ->will($this->returnValue($events));

        $result = $this->provider->getCalendarEvents($userId, $calendarId, $start, $end, $subordinate);
        $this->assertEquals($events, $result);
    }
}
