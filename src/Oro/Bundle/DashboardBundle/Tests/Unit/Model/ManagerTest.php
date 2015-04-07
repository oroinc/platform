<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

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

        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $this->manager = new Manager(
            $this->factory,
            $this->entityManager,
            $this->aclHelper,
            $this->securityContext
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

    public function testFindOneDashboardModelBy()
    {
        $criteria = array('label' => 'Foo');
        $orderBy = array('label' => 'ASC');

        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria, $orderBy)
            ->will($this->returnValue($dashboard));

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->will($this->returnValue($dashboardModel));

        $this->assertEquals($dashboardModel, $this->manager->findOneDashboardModelBy($criteria, $orderBy));
    }

    public function testFindOneDashboardModelByEmpty()
    {
        $criteria = array('label' => 'Foo');
        $orderBy = array('label' => 'ASC');

        $this->dashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria, $orderBy)
            ->will($this->returnValue(null));

        $this->assertNull($this->manager->findOneDashboardModelBy($criteria, $orderBy));
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
        $organization   = new Organization();
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory->expects($this->once())
            ->method('createDashboardModel')
            ->with($this->isInstanceOf('Oro\Bundle\DashboardBundle\Entity\Dashboard'))
            ->will($this->returnValue($dashboardModel));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

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
                        return true;
                    }
                )
            )
            ->will($this->returnValue($widgetModel));

        $this->assertEquals($widgetModel, $this->manager->createWidgetModel($widgetName));
    }

    public function testSave()
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMock('Oro\Bundle\DashboardBundle\Model\EntityModelInterface');
        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($widgetEntity);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->manager->save($widgetModel);
    }

    public function testSaveWithFlush()
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMock('Oro\Bundle\DashboardBundle\Model\EntityModelInterface');
        $widgetModel->expects($this->exactly(2))
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($widgetEntity);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with($widgetEntity);

        $this->manager->save($widgetModel, true);
    }

    public function testSaveDashboardWithoutCopyEmptyStartDashboard()
    {
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel->expects($this->once())
            ->method('getStartDashboard')
            ->will($this->returnValue(null));

        $dashboardModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($dashboardEntity));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($dashboardEntity);

        $this->manager->save($dashboardModel, false);
    }

    public function testSaveDashboardWithoutCopyNotNew()
    {
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $startDashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel->expects($this->once())
            ->method('getStartDashboard')
            ->will($this->returnValue($startDashboardEntity));

        $dashboardModel->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $dashboardModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($dashboardEntity));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($dashboardEntity);

        $this->manager->save($dashboardModel, false);
    }

    public function testSaveDashboardWithCopy()
    {
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $startDashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $startDashboardWidgets = array(
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget'),
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget'),
        );
        $copyWidgets = array(
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget'),
            $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget'),
        );
        $copyWidgetModels = array(
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
                ->disableOriginalConstructor()
                ->getMock(),
        );

        $expectedCopyWidgetsData = array(
            array('layoutPosition' => array(0, 1), 'name' => 'foo'),
            array('layoutPosition' => array(1, 0), 'name' => 'bar'),
        );

        $dashboardModel->expects($this->exactly(2))
            ->method('getStartDashboard')
            ->will($this->returnValue($startDashboardEntity));

        $dashboardModel->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $startDashboardEntity->expects($this->once())
            ->method('getWidgets')
            ->will($this->returnValue($startDashboardWidgets));

        $index = 0;

        /** @var \PHPUnit_Framework_MockObject_MockObject $widgetMock */
        foreach ($startDashboardWidgets as $index => $widgetMock) {
            $expectedData = $expectedCopyWidgetsData[$index];

            $widgetMock->expects($this->once())
                ->method('getLayoutPosition')
                ->will($this->returnValue($expectedData['layoutPosition']));

            $widgetMock->expects($this->once())
                ->method('getName')
                ->will($this->returnValue($expectedData['name']));

            $widgetMock->expects($this->once())
                ->method('getOptions')
                ->will($this->returnValue([]));

            $this->factory->expects($this->at($index))
                ->method('createWidgetModel')
                ->with(
                    $this->callback(
                        function ($entity) use ($expectedData) {
                            $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\Widget', $entity);
                            $this->assertEquals($expectedData['layoutPosition'], $entity->getLayoutPosition());
                            $this->assertEquals($expectedData['name'], $entity->getName());
                            return true;
                        }
                    )
                )
                ->will($this->returnValue($copyWidgetModels[$index]));

            $copyWidgetModels[$index]->expects($this->once())
                ->method('getEntity')
                ->will($this->returnValue($copyWidgets[$index]));

            $this->entityManager->expects($this->at($index))
                ->method('persist')
                ->with($copyWidgets[$index]);
        }

        $dashboardModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($dashboardEntity));

        $this->entityManager->expects($this->at($index + 1))
            ->method('persist')
            ->with($dashboardEntity);

        $this->manager->save($dashboardModel, false);
    }

    public function testRemove()
    {
        $widgetEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widgetModel = $this->getMock('Oro\Bundle\DashboardBundle\Model\EntityModelInterface');
        $widgetModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($widgetEntity));

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($widgetEntity);

        $this->manager->remove($widgetModel);
    }

    public function testFindUserActiveDashboard()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $activeDashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard');
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user, 'organization' => $organization))
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
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user, 'organization' => $organization))
            ->will($this->returnValue(null));

        $this->factory->expects($this->never())->method($this->anything());

        $this->assertNull($this->manager->findUserActiveDashboard($user));
    }

    public function testFindDefaultDashboard()
    {
        $organization = new Organization();
        $dashboardEntity = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->dashboardRepository->expects($this->once())
            ->method('findDefaultDashboard')
            ->with($organization)
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
        $organization = new Organization();
        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

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
        $organization       = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $activeDashboard    = $this->getMock('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard');
        $dashboard          = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())->method('getEntity')->will($this->returnValue($dashboard));

        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user, 'organization' => $organization))
            ->will($this->returnValue($activeDashboard));

        $activeDashboard->expects($this->once())->method('setDashboard')->with($dashboard);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush')->with($activeDashboard);

        $this->manager->setUserActiveDashboard($dashboardModel, $user, true);
    }

    public function testSetUserActiveDashboardCreateNew()
    {
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $dashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboardModel->expects($this->once())->method('getEntity')->will($this->returnValue($dashboard));

        $user           = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $organization   = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->activeDashboardRepository->expects($this->once())
            ->method('findOneBy')
            ->with(array('user' => $user, 'organization' => $organization))
            ->will($this->returnValue(null));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with($this->isInstanceOf('Oro\Bundle\DashboardBundle\Entity\ActiveDashboard'));

        $this->manager->setUserActiveDashboard($dashboardModel, $user, true);
    }
}
