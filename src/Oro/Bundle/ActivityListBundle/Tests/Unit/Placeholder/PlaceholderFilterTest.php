<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Tests\Unit\Stub\TestTarget;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestNonActiveTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestNonManagedTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceholderFilterTest extends TestCase
{
    private ActivityListChainProvider&MockObject $activityListProvider;
    private ActivityListRepository&MockObject $repository;
    private ConfigManager&MockObject $configManager;
    private PlaceholderFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->activityListProvider = $this->createMock(ActivityListChainProvider::class);
        $this->repository = $this->createMock(ActivityListRepository::class);

        $this->activityListProvider->expects($this->any())
            ->method('getTargetEntityClasses')
            ->willReturn([TestTarget::class]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) {
                return is_object($entity) ? get_class($entity) : $entity;
            });
        $doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturnCallback(function ($entity) {
                return null === $entity->getId();
            });
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) {
                return $entity->getId();
            });
        $doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(function ($entity) {
                return !$entity instanceof TestNonManagedTarget;
            });
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->filter = new PlaceholderFilter(
            $this->activityListProvider,
            $doctrineHelper,
            $this->configManager
        );
    }

    public function testIsApplicableNoSupportedActivities(): void
    {
        $testTarget = new TestTarget(1);

        $entityClass = get_class($testTarget);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::VIEW_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->activityListProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn([]);

        $this->assertFalse($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableWithSupportedActivities(): void
    {
        $testTarget = new TestTarget(1);

        $entityClass = get_class($testTarget);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::VIEW_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->activityListProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn([$activityClass]);

        $this->activityListProvider->expects($this->once())
            ->method('isApplicableTarget')
            ->with($entityClass, $activityClass)
            ->willReturn(true);

        $this->assertTrue($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableWithNonManagedEntity(): void
    {
        $testTarget = new TestNonManagedTarget(1);
        $this->assertFalse($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableForNotSupportedPage(): void
    {
        $testTarget = new TestTarget(1);

        $entityClass = get_class($testTarget);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::UPDATE_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->assertFalse($this->filter->isApplicable($testTarget, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableOnNonSupportedTarget(): void
    {
        $entity = new TestNonActiveTarget(123);

        $entityClass = get_class($entity);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::VIEW_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->activityListProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn([$activityClass]);

        $this->activityListProvider->expects($this->once())
            ->method('isApplicableTarget')
            ->with($entityClass, $activityClass)
            ->willReturn(false);

        $this->assertFalse($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicableOnEmptyActivityList(): void
    {
        $this->repository->expects($this->any())
            ->method('getRecordsCountForTargetClassAndId')
            ->with(TestNonActiveTarget::class, 123)
            ->willReturn(0);

        $entity = new TestNonActiveTarget(123);

        $entityClass = get_class($entity);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::VIEW_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->activityListProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn([$activityClass]);

        $this->activityListProvider->expects($this->once())
            ->method('isApplicableTarget')
            ->with($entityClass, $activityClass)
            ->willReturn(true);

        $this->assertFalse($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));
    }

    public function testIsApplicable(): void
    {
        $this->repository->expects($this->any())
            ->method('getRecordsCountForTargetClassAndId')
            ->with(TestNonActiveTarget::class, 123)
            ->willReturn(10);

        $entity = new TestNonActiveTarget(123);

        $entityClass = get_class($entity);
        $activityClass = 'Test\Activity';

        $config = new Config(
            new EntityConfigId('activity', $entityClass)
        );
        $config->set(ActivityScope::SHOW_ON_PAGE, ActivityScope::VIEW_PAGE);
        $config->set('activities', [$activityClass]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', $entityClass)
            ->willReturn($config);

        $this->activityListProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn([$activityClass]);

        $this->activityListProvider->expects($this->once())
            ->method('isApplicableTarget')
            ->with($entityClass, $activityClass)
            ->willReturn(true);

        $this->assertTrue($this->filter->isApplicable($entity, ActivityScope::VIEW_PAGE));
    }

    /**
     * @dataProvider isAllowedButtonProvider
     */
    public function testIsAllowedButton(
        int $pageType,
        array $widgets,
        object $entity,
        ?int $configProviderSetting,
        array $expected
    ): void {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($entity))
            ->willReturn(true);

        if ($configProviderSetting !== null) {
            $config = new Config(
                new EntityConfigId('activity', get_class($entity))
            );
            $config->set(ActivityScope::SHOW_ON_PAGE, $configProviderSetting);

            $this->configManager->expects($this->once())
                ->method('getEntityConfig')
                ->with('activity', get_class($entity))
                ->willReturn($config);
        }
        $event = new BeforeGroupingChainWidgetEvent($pageType, $widgets, $entity);
        $this->filter->isAllowedButton($event);
        $this->assertEquals($expected, $event->getWidgets());
    }

    public function isAllowedButtonProvider(): array
    {
        $widgets = ['array' => 'of widgets'];
        $entity = new TestTarget(1);

        return [
            'entity with "update" activity entity config and "view" event' => [
                'groupType'             => ActivityScope::VIEW_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => ActivityScope::UPDATE_PAGE,
                'expected'              => []
            ],
            'new entity with "update" activity' => [
                'groupType'             => ActivityScope::UPDATE_PAGE,
                'widgets'               => $widgets,
                'entity'                => new TestTarget(null),
                'configProviderSetting' => ActivityScope::UPDATE_PAGE,
                'expected'              => []
            ],
            'entity with "view/update" activity entity config and "view" event' => [
                'groupType'             => ActivityScope::VIEW_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => ActivityScope::VIEW_UPDATE_PAGES,
                'expected'              => $widgets
            ],
            'entity with "view/update" activity entity config and "update" event' => [
                'groupType'             => ActivityScope::UPDATE_PAGE,
                'widgets'               => $widgets,
                'entity'                => $entity,
                'configProviderSetting' => ActivityScope::VIEW_UPDATE_PAGES,
                'expected'              => $widgets
            ],
        ];
    }
}
