<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\EntityModelInterface;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Factory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var DashboardRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $dashboardRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $activeDashboardRepository;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var Manager */
    private $manager;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(Factory::class);
        $this->dashboardRepository = $this->createMock(DashboardRepository::class);
        $this->widgetRepository = $this->createMock(EntityRepository::class);
        $this->activeDashboardRepository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->entityManager->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [Dashboard::class, $this->dashboardRepository],
                [Widget::class, $this->widgetRepository],
                [ActiveDashboard::class, $this->activeDashboardRepository],
            ]);

        $this->manager = new Manager(
            $this->factory,
            $this->entityManager,
            $this->aclHelper,
            $this->tokenStorage
        );
    }

    public function testFindDashboardModel(): void
    {
        $id = 100;

        $dashboard = $this->createMock(Dashboard::class);
        $dashboardModel = $this->createMock(DashboardModel::class);

        $this->dashboardRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($dashboard);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->willReturn($dashboardModel);

        self::assertEquals($dashboardModel, $this->manager->findDashboardModel($id));
    }

    public function testFindDashboardModelEmpty(): void
    {
        $id = 100;

        $this->dashboardRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        self::assertNull($this->manager->findDashboardModel($id));
    }

    public function testFindOneDashboardModelBy(): void
    {
        $criteria = ['label' => 'Foo'];
        $orderBy = ['label' => 'ASC'];

        $dashboard = $this->createMock(Dashboard::class);
        $dashboardModel = $this->createMock(DashboardModel::class);

        $this->dashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria, $orderBy)
            ->willReturn($dashboard);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->willReturn($dashboardModel);

        self::assertEquals($dashboardModel, $this->manager->findOneDashboardModelBy($criteria, $orderBy));
    }

    public function testFindOneDashboardModelByEmpty(): void
    {
        $criteria = ['label' => 'Foo'];
        $orderBy = ['label' => 'ASC'];

        $this->dashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria, $orderBy)
            ->willReturn(null);

        self::assertNull($this->manager->findOneDashboardModelBy($criteria, $orderBy));
    }

    public function testFindWidgetModel(): void
    {
        $id = 100;

        $widget = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(WidgetModel::class);

        $this->widgetRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($widget);

        $this->factory->expects(self::once())
            ->method('createWidgetModel')
            ->with($widget)
            ->willReturn($widgetModel);

        self::assertEquals($widgetModel, $this->manager->findWidgetModel($id));
    }

    public function testFindWidgetModelEmpty(): void
    {
        $id = 100;

        $this->widgetRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        self::assertNull($this->manager->findWidgetModel($id));
    }

    public function testGetDashboardModel(): void
    {
        $dashboard = $this->createMock(Dashboard::class);
        $dashboardModel = $this->createMock(DashboardModel::class);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($dashboard)
            ->willReturn($dashboardModel);

        self::assertEquals($dashboardModel, $this->manager->getDashboardModel($dashboard));
    }

    public function testGetWidgetModel(): void
    {
        $widget = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(WidgetModel::class);

        $this->factory->expects(self::once())
            ->method('createWidgetModel')
            ->with($widget)
            ->willReturn($widgetModel);

        self::assertEquals($widgetModel, $this->manager->getWidgetModel($widget));
    }

    public function testGetDashboardModels(): void
    {
        /** @var Dashboard[]|\PHPUnit\Framework\MockObject\MockObject[] $entities */
        $entities = [
            $this->createMock(Dashboard::class),
            $this->createMock(Dashboard::class)
        ];
        /** @var DashboardModel[]|\PHPUnit\Framework\MockObject\MockObject[] $dashboardModels */
        $dashboardModels = [
            $this->createMock(DashboardModel::class),
            $this->createMock(DashboardModel::class)
        ];

        $this->factory->expects(self::exactly(2))
            ->method('createDashboardModel')
            ->willReturnMap([
                [$entities[0], $dashboardModels[0]],
                [$entities[1], $dashboardModels[1]]
            ]);

        self::assertEquals($dashboardModels, $this->manager->getDashboardModels($entities));
    }

    public function testCreateDashboardModel(): void
    {
        $dashboardModel = $this->createMock(DashboardModel::class);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($this->isInstanceOf(Dashboard::class))
            ->willReturn($dashboardModel);

        self::assertEquals($dashboardModel, $this->manager->createDashboardModel());
    }

    public function testCreateWidgetModel(): void
    {
        $widgetName = 'test';

        $widgetModel = $this->createMock(WidgetModel::class);

        $this->factory->expects(self::once())
            ->method('createWidgetModel')
            ->with(
                $this->callback(
                    function ($entity) use ($widgetName) {
                        $this->assertInstanceOf(Widget::class, $entity);
                        self::assertEquals($widgetName, $entity->getName());

                        return true;
                    }
                )
            )
            ->willReturn($widgetModel);

        self::assertEquals($widgetModel, $this->manager->createWidgetModel($widgetName));
    }

    public function testSave(): void
    {
        $widgetEntity = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(EntityModelInterface::class);
        $widgetModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($widgetEntity);

        $this->entityManager->expects(self::never())
            ->method('flush');

        $this->manager->save($widgetModel);
    }

    public function testSaveWithFlush(): void
    {
        $widgetEntity = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(EntityModelInterface::class);
        $widgetModel->expects(self::exactly(2))
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($widgetEntity);

        $this->entityManager->expects(self::once())
            ->method('flush')
            ->with($widgetEntity);

        $this->manager->save($widgetModel, true);
    }

    public function testSaveDashboardWithoutCopyEmptyStartDashboard(): void
    {
        $dashboardModel = $this->createMock(DashboardModel::class);
        $dashboardEntity = $this->createMock(Dashboard::class);

        $dashboardModel->expects(self::once())
            ->method('getStartDashboard')
            ->willReturn(null);

        $dashboardModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($dashboardEntity);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($dashboardEntity);

        $this->manager->save($dashboardModel);
    }

    public function testSaveDashboardWithoutCopyNotNew(): void
    {
        $dashboardModel = $this->createMock(DashboardModel::class);

        $startDashboardEntity = $this->createMock(Dashboard::class);
        $dashboardEntity = $this->createMock(Dashboard::class);

        $dashboardModel->expects(self::once())
            ->method('getStartDashboard')
            ->willReturn($startDashboardEntity);

        $dashboardModel->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $dashboardModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($dashboardEntity);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($dashboardEntity);

        $this->manager->save($dashboardModel);
    }

    public function testSaveDashboardWithCopy(): void
    {
        $dashboardModel = $this->createMock(DashboardModel::class);

        $startDashboardEntity = $this->createMock(Dashboard::class);
        $dashboardEntity = $this->createMock(Dashboard::class);
        /** @var Widget[]|\PHPUnit\Framework\MockObject\MockObject[] $startDashboardWidgets */
        $startDashboardWidgets = [
            $this->createMock(Widget::class),
            $this->createMock(Widget::class),
        ];
        /** @var Widget[]|\PHPUnit\Framework\MockObject\MockObject[] $copyWidgets */
        $copyWidgets = [
            $this->createMock(Widget::class),
            $this->createMock(Widget::class),
        ];
        /** @var WidgetModel[]|\PHPUnit\Framework\MockObject\MockObject[] $copyWidgetModels */
        $copyWidgetModels = [
            $this->createMock(WidgetModel::class),
            $this->createMock(WidgetModel::class)
        ];

        $expectedCopyWidgetsData = [
            ['layoutPosition' => [0, 1], 'name' => 'foo'],
            ['layoutPosition' => [1, 0], 'name' => 'bar'],
        ];

        $dashboardModel->expects(self::exactly(2))
            ->method('getStartDashboard')
            ->willReturn($startDashboardEntity);

        $dashboardModel->expects(self::once())
            ->method('getId')
            ->willReturn(null);

        $startDashboardEntity->expects(self::once())
            ->method('getWidgets')
            ->willReturn($startDashboardWidgets);

        $persists = [];
        $widgetModelReturnCallbacks = [];
        foreach ($startDashboardWidgets as $index => $widget) {
            $expectedData = $expectedCopyWidgetsData[$index];

            $widget->expects(self::once())
                ->method('getLayoutPosition')
                ->willReturn($expectedData['layoutPosition']);
            $widget->expects(self::once())
                ->method('getName')
                ->willReturn($expectedData['name']);
            $widget->expects(self::once())
                ->method('getOptions')
                ->willReturn([]);

            $widgetModel = $copyWidgetModels[$index];
            $widgetModel->expects(self::once())
                ->method('getEntity')
                ->willReturn($copyWidgets[$index]);

            $widgetModelReturnCallbacks[] = new ReturnCallback(function ($entity) use ($expectedData, $widgetModel) {
                $this->assertInstanceOf(Widget::class, $entity);
                self::assertEquals($expectedData['layoutPosition'], $entity->getLayoutPosition());
                self::assertEquals($expectedData['name'], $entity->getName());

                return $widgetModel;
            });

            $persists[] = [$copyWidgets[$index]];
        }
        $this->factory->expects(self::exactly(count($widgetModelReturnCallbacks)))
            ->method('createWidgetModel')
            ->willReturnOnConsecutiveCalls(...$widgetModelReturnCallbacks);

        $persists[] = [$dashboardEntity];
        $this->entityManager->expects(self::exactly(count($persists)))
            ->method('persist')
            ->withConsecutive(...$persists);

        $dashboardModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($dashboardEntity);

        $this->manager->save($dashboardModel);
    }

    public function testRemove(): void
    {
        $widgetEntity = $this->createMock(Widget::class);
        $widgetModel = $this->createMock(EntityModelInterface::class);
        $widgetModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($widgetEntity);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with($widgetEntity);

        $this->manager->remove($widgetModel);
    }

    public function testFindUserActiveDashboard(): void
    {
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);
        $activeDashboardEntity = $this->createMock(ActiveDashboard::class);
        $dashboardEntity = $this->createMock(Dashboard::class);
        $dashboardModel = $this->createMock(DashboardModel::class);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->activeDashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user, 'organization' => $organization])
            ->willReturn($activeDashboardEntity);

        $activeDashboardEntity->expects(self::once())
            ->method('getDashboard')
            ->willReturn($dashboardEntity);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($dashboardEntity)
            ->willReturn($dashboardModel);

        self::assertEquals(
            $dashboardModel,
            $this->manager->findUserActiveDashboard($user)
        );
    }

    public function testFindUserActiveDashboardEmpty(): void
    {
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->activeDashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user, 'organization' => $organization])
            ->willReturn(null);

        $this->factory->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->manager->findUserActiveDashboard($user));
    }

    public function testFindDefaultDashboard(): void
    {
        $organization = new Organization();
        $dashboardEntity = $this->createMock(Dashboard::class);
        $dashboardModel = $this->createMock(DashboardModel::class);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->dashboardRepository->expects(self::once())
            ->method('findDefaultDashboard')
            ->with($organization)
            ->willReturn($dashboardEntity);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($dashboardEntity)
            ->willReturn($dashboardModel);

        self::assertEquals(
            $dashboardModel,
            $this->manager->findDefaultDashboard()
        );
    }

    public function testFindDefaultDashboardEmpty(): void
    {
        $organization = new Organization();
        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->dashboardRepository->expects(self::once())
            ->method('findDefaultDashboard')
            ->willReturn(null);

        $this->factory->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->manager->findDefaultDashboard());
    }

    public function testFindAllowedDashboards(): void
    {
        $permission = 'EDIT';
        $expectedEntities = [$this->createMock(Dashboard::class)];
        /** @var DashboardModel[]|\PHPUnit\Framework\MockObject\MockObject[] $expectedModels */
        $expectedModels = [
            $this->createMock(DashboardModel::class)
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedEntities);

        $this->dashboardRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('dashboard')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder, $permission)
            ->willReturn($query);

        $this->factory->expects(self::once())
            ->method('createDashboardModel')
            ->with($expectedEntities[0])
            ->willReturn($expectedModels[0]);

        self::assertEquals(
            $expectedModels,
            $this->manager->findAllowedDashboards($permission)
        );
    }

    public function testFindAllowedDashboardsShortenedInfo(): void
    {
        $permission = 'EDIT';
        $expectedInfo = [['id' => 1, 'label' => 'test label 1'], ['id' => 2, 'label' => 'test label 2']];

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedInfo);

        $this->dashboardRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('dashboard')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('dashboard.id, dashboard.label')
            ->willReturnSelf();

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder, $permission)
            ->willReturn($query);

        self::assertEquals(
            $expectedInfo,
            $this->manager->findAllowedDashboardsShortenedInfo($permission)
        );
    }

    public function testFindAllowedDashboardsShortenedInfoFilteredByOrganization(): void
    {
        $permission = 'EDIT';
        $organizationId = 42;
        $expectedInfo = [['id' => 1, 'label' => 'test label 1'], ['id' => 2, 'label' => 'test label 2']];

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedInfo);

        $this->dashboardRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('dashboard')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('dashboard.id, dashboard.label')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->willReturnSelf();

        $expr = $this->createMock(Expr::class);
        $expr->expects(self::once())
            ->method('eq')
            ->with('dashboard.organization', ':organizationId');

        $queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn($expr);

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('organizationId', $organizationId)
            ->willReturnSelf();

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder, $permission)
            ->willReturn($query);

        self::assertEquals(
            $expectedInfo,
            $this->manager->findAllowedDashboardsShortenedInfo($permission, $organizationId)
        );
    }

    public function testSetUserActiveDashboardOverrideExistOne(): void
    {
        $organization = $this->createMock(Organization::class);
        $activeDashboard = $this->createMock(ActiveDashboard::class);
        $dashboard = $this->createMock(Dashboard::class);

        $dashboardModel = $this->createMock(DashboardModel::class);
        $dashboardModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($dashboard);

        $user = $this->createMock(User::class);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->activeDashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user, 'organization' => $organization])
            ->willReturn($activeDashboard);

        $activeDashboard->expects(self::once())
            ->method('setDashboard')
            ->with($dashboard);
        $this->entityManager->expects(self::never())
            ->method('persist');
        $this->entityManager->expects(self::once())
            ->method('flush')
            ->with($activeDashboard);

        $this->manager->setUserActiveDashboard($dashboardModel, $user, true);
    }

    public function testSetUserActiveDashboardCreateNew(): void
    {
        $dashboard = $this->createMock(Dashboard::class);

        $dashboardModel = $this->createMock(DashboardModel::class);
        $dashboardModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($dashboard);

        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->activeDashboardRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user, 'organization' => $organization])
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('persist');
        $this->entityManager->expects(self::once())
            ->method('flush')
            ->with($this->isInstanceOf(ActiveDashboard::class));

        $this->manager->setUserActiveDashboard($dashboardModel, $user, true);
    }
}
