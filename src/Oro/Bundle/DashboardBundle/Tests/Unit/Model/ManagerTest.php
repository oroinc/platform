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
    protected $factory;

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
        $this->markTestSkipped();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository = $this->getMockBuilder(
            'Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository'
        )->disableOriginalConstructor()
         ->getMock();

        $this->factory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new Manager(
            $this->factory,
            $this->entityManager
        );
    }

    public function testSetUserActiveDashboard()
    {
        $this->markTestSkipped();
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $id = 42;
        $expected = array('user' => $user);
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $activeDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard');
        $this->assertFalse($this->manager->setUserActiveDashboard($user, $id));
        $this->dashboardRepository->expects($this->once())
            ->method('getAvailableDashboard')
            ->with($id)
            ->will($this->returnValue($dashboard));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with($expected)
            ->will($this->returnValue($activeDashboard));
        $activeDashboard->expects($this->once())->method('setDashboard')->with($dashboard);
        $this->entityManager->expects($this->once())->method('persist')->with($activeDashboard);
        $this->entityManager->expects($this->once())->method('getRepository')->will($this->returnValue($repository));
        $this->assertTrue($this->manager->setUserActiveDashboard($user, $id));
    }
}
