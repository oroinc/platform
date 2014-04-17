<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Model\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $activeDashboardRepository;

    /**
     * @var Manager
     */
    protected $manager;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository =
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->activeDashboardRepository =
            $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())->method('getRepository')
            ->will(
                $this->returnValueMap(
                    array(
                        array('OroDashboardBundle:Dashboard', $this->dashboardRepository),
                        array('OroDashboardBundle:ActiveDashboard', $this->activeDashboardRepository),
                    )
                )
            );

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new Manager(
            $this->factory,
            $this->entityManager,
            $this->aclHelper
        );
    }

    public function testSetUserActiveDashboardOverrideExistOne()
    {
        $activeDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard');
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())->method('getEntity')->will($this->returnValue($dashboard));

        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user))
            ->will($this->returnValue($activeDashboard));

        $activeDashboard->expects($this->once())->method('setDashboard')->with($dashboard);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->setUserActiveDashboard($dashboardModel, $user);
    }

    public function testSetUserActiveDashboardCreateNew()
    {
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())->method('getEntity')->will($this->returnValue($dashboard));

        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user))
            ->will($this->returnValue(null));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($activeDashboard) use ($user, $dashboard) {
                        /** @var ActiveDashboard $activeDashboard */
                        $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard', $activeDashboard);
                        $this->assertEquals($user, $activeDashboard->getUser());
                        $this->assertEquals($dashboard, $activeDashboard->getDashboard());
                        return true;
                    }
                )
            );

        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->setUserActiveDashboard($dashboardModel, $user);
    }
}
