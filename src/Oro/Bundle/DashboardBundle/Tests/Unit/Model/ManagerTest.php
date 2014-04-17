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
    protected $widgetRepository;

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

        $this->widgetRepository =
            $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();

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
                        array('OroDashboardBundle:Widget', $this->widgetRepository),
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

    public function testFindDashboardModel()
    {
        $id = 100;

        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($dashboard));

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->will($this->returnValue($dashboardModel));

        $this->assertEquals($dashboardModel, $this->manager->findDashboardModel($id));
    }

    public function testFindDashboardModelEmpty()
    {
        $id = 100;

        $this->dashboardRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->assertNull($this->manager->findDashboardModel($id));
    }

    public function testFindWidgetModel()
    {
        $id = 100;

        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($widget));

        $this->factory->expects($this->once())
            ->method('createWidgetModel')
            ->with($widget)
            ->will($this->returnValue($widgetModel));

        $this->assertEquals($widgetModel, $this->manager->findWidgetModel($id));
    }

    public function testFindWidgetModelEmpty()
    {
        $id = 100;

        $this->widgetRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->assertNull($this->manager->findWidgetModel($id));
    }

    public function testGetDashboardModel()
    {
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->will($this->returnValue($dashboardModel));

        $this->assertEquals($dashboardModel, $this->manager->getDashboardModel($dashboard));
    }

    public function testGetWidgetModel()
    {
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory->expects($this->once())
            ->method('createWidgetModel')
            ->with($widget)
            ->will($this->returnValue($widgetModel));

        $this->assertEquals($widgetModel, $this->manager->getWidgetModel($widget));
    }

    public function testGetDashboardModels()
    {
        $entities = array(
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard'),
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard')
        );

        $dashboardModels = array(
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
                ->disableOriginalConstructor()
                ->getMock()
        );

        $this->factory->expects($this->exactly(2))
            ->method('createDashboardModel')
            ->will(
                $this->returnValueMap(
                    array(
                        array($entities[0], $dashboardModels[0]),
                        array($entities[1], $dashboardModels[1])
                    )
                )
            );

        $this->assertEquals($dashboardModels, $this->manager->getDashboardModels($entities));
    }

    public function testCreateDashboardModel()
    {
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($this->isInstanceOf('Oro\Bundle\DashboardBundle\Entity\Dashboard'))
            ->will($this->returnValue($dashboardModel));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\DashboardBundle\Entity\Dashboard'));

        $this->assertEquals($dashboardModel, $this->manager->createDashboardModel());
    }

    public function testCreateWidgetModel()
    {
        $widgetName = 'test';

        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory->expects($this->once())
            ->method('createWidgetModel')
            ->with(
                $this->callback(
                    function ($entity) use ($widgetName) {
                        $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\Widget', $entity);
                        $this->assertEquals($widgetName, $entity->getName());
                        $this->assertTrue($entity->isExpanded());
                        return true;
                    }
                )
            )
            ->will($this->returnValue($widgetModel));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\DashboardBundle\Entity\Widget'));

        $this->assertEquals($widgetModel, $this->manager->createWidgetModel($widgetName));
    }

    public function testRemoveWidgetModel()
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();
        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($widgetEntity);

        $this->manager->removeWidgetModel($widgetModel);
    }

    public function testRemoveDashboardModel()
    {
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($dashboardEntity));

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($dashboardEntity);

        $this->manager->removeDashboardModel($dashboardModel);
    }

    public function testFindUserActiveDashboard()
    {
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $activeDashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard');
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user))
            ->will($this->returnValue($activeDashboardEntity));

        $activeDashboardEntity->expects($this->once())
            ->method('getDashboard')
            ->will($this->returnValue($dashboardEntity));

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($dashboardEntity)
            ->will($this->returnValue($dashboardModel));

        $this->assertEquals(
            $dashboardModel,
            $this->manager->findUserActiveDashboard($user)
        );
    }

    public function testFindUserActiveDashboardEmpty()
    {
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user))
            ->will($this->returnValue(null));

        $this->factory->expects($this->never())->method($this->anything());

        $this->assertNull($this->manager->findUserActiveDashboard($user));
    }

    public function testFindDefaultDashboard()
    {
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository->expects($this->once())
            ->method('findDefaultDashboard')
            ->will($this->returnValue($dashboardEntity));

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($dashboardEntity)
            ->will($this->returnValue($dashboardModel));

        $this->assertEquals(
            $dashboardModel,
            $this->manager->findDefaultDashboard()
        );
    }

    public function testFindDefaultDashboardEmpty()
    {
        $this->dashboardRepository->expects($this->once())
            ->method('findDefaultDashboard')
            ->will($this->returnValue(null));

        $this->factory->expects($this->never())->method($this->anything());

        $this->assertNull($this->manager->findDefaultDashboard());
    }

    public function testFindAllowedDashboards()
    {
        $permission = 'EDIT';
        $expectedEntities = array($this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard'));
        $expectedModels = array(
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
                ->disableOriginalConstructor()
                ->getMock()
        );

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($expectedEntities));

        $this->dashboardRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('dashboard')
            ->will($this->returnValue($queryBuilder));

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder, $permission)
            ->will($this->returnValue($query));

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($expectedEntities[0])
            ->will($this->returnValue($expectedModels[0]));

        $this->assertEquals(
            $expectedModels,
            $this->manager->findAllowedDashboards($permission)
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

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->setUserActiveDashboard($dashboardModel, $user);
    }
}
