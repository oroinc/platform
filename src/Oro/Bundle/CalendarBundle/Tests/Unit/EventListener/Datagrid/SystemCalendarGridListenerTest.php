<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\TestGridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemCalendarGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemCalendarGridListener */
    protected $listener;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $securityContextLink;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $aclVoter;

    protected function setUp()
    {
        $organization = new Organization();
        $organization->setId(1);

        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $securityContext->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->securityContextLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContextLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($securityContext));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoter->expects($this->any())
            ->method('addOneShotIsGrantedObserver');

        $this->listener = new SystemCalendarGridListener(
            $this->securityContextLink,
            $this->securityFacade,
            $this->aclVoter
        );
    }

    public function testOnBuildBeforeViewGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $parameters = new ParameterBag(['entityField' => 'testField']);
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($parameters));

        $gridConfig = new TestGridConfiguration();

        $event = new BuildBefore($datagrid, $gridConfig);
        $this->listener->onBuildBefore($event);

        $where = $gridConfig->offsetGetByPath(SystemCalendarGridListener::GRID_WHERE_PATH);
        $this->assertEquals(
            [
                'o.id in (1)',
            ],
            $where
        );
    }

    public function testOnBuildBeforeViewNotGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $parameters = new ParameterBag(['entityField' => 'testField']);
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($parameters));

        $gridConfig = new TestGridConfiguration();

        $event = new BuildBefore($datagrid, $gridConfig);
        $this->listener->onBuildBefore($event);

        $where = $gridConfig->offsetGetByPath(SystemCalendarGridListener::GRID_WHERE_PATH);
        $this->assertEquals(
            [
                'o.id in (1)',
                'sc.public = 1',
            ],
            $where
        );
    }
}
