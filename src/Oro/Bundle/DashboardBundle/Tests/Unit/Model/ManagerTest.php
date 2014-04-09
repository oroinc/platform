<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
     /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardModelFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository = $this->getMockBuilder(
            'Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository'
        )->disableOriginalConstructor()
         ->getMock();

        $this->dashboardModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new Manager(
            $this->configProvider,
            $this->dashboardRepository,
            $this->dashboardModelFactory,
            $this->entityManager
        );
    }

    public function testGetDashboards()
    {
        $firstDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $secondDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboards = array($firstDashboard, $secondDashboard);
        $this->dashboardRepository->expects($this->once())
            ->method('getAvailableDashboards')
            ->will($this->returnValue($dashboards));
        $this->dashboardModelFactory->expects($this->exactly(2))
            ->method('getDashboardModel')
            ->with($secondDashboard);
        $dashboards = $this->manager->getDashboards();

        $this->assertCount(2, $dashboards);
    }

    public function testGetUserDashboardIfActiveExist()
    {
        $expectedId = 42;
        $expected = array('user_id' => $expectedId);
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('\Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())->method('getDashboard')->will($this->returnValue($dashboard));
        $user->expects($this->once())->method('getId')->will($this->returnValue($expectedId));
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with($expected)
            ->will($this->returnValue($dashboardModel));
        $this->entityManager->expects($this->once())->method('getRepository')->will($this->returnValue($repository));
        $this->manager->getUserDashboard($user);
    }

    public function testGetUserDashboardIfActiveNotExist()
    {
        $expectedName = 'main';
        $expected = array('name' => $expectedName);
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $user->expects($this->once())->method('getId')->will($this->returnValue(42));
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $this->configProvider->expects($this->once())->method('getConfig')->will($this->returnValue($expectedName));
        $this->dashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with($expected)
            ->will($this->returnValue($dashboard));
        $this->entityManager->expects($this->once())->method('getRepository')->will($this->returnValue($repository));
        $this->manager->getUserDashboard($user);
    }
}
