<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Api;

use Oro\Bundle\ActivityBundle\Api\ActivityAssociationProvider;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ActivityAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var ResourcesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesProvider;

    /** @var ActivityAssociationProvider */
    private $activityAssociationProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->activityAssociationProvider = new ActivityAssociationProvider(
            ['Test\AnotherActivity' => 'renamedActivities'],
            $this->doctrineHelper,
            $this->configManager,
            $this->activityManager,
            $this->resourcesProvider
        );
    }

    public function testIsActivityEntity(): void
    {
        $entityClass = get_class($this->createMock(ActivityInterface::class));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        self::assertTrue($this->activityAssociationProvider->isActivityEntity($entityClass));
        // test memory cache
        self::assertTrue($this->activityAssociationProvider->isActivityEntity($entityClass));
    }

    public function testIsActivityEntityForNotConfigurableEntity(): void
    {
        $entityClass = get_class($this->createMock(ActivityInterface::class));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
        // test memory cache
        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
    }

    public function testIsActivityEntityForNotManageableEntity(): void
    {
        $entityClass = get_class($this->createMock(ActivityInterface::class));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('hasConfig');

        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
        // test memory cache
        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
    }

    public function testIsActivityEntityForNotActivityEntity(): void
    {
        $entityClass = \stdClass::class;

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->configManager->expects(self::never())
            ->method('hasConfig');

        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
        // test memory cache
        self::assertFalse($this->activityAssociationProvider->isActivityEntity($entityClass));
    }

    public function testGetActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityManager->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass)
            ->willReturn(
                [
                    ['className' => 'Test\Activity', 'associationName' => 'activity'],
                    ['className' => 'Test\AnotherActivity', 'associationName' => 'another'],
                    ['className' => 'Test\NotAccessibleActivity', 'associationName' => 'not_accessible'],
                ]
            );
        $this->resourcesProvider->expects(self::exactly(3))
            ->method('isResourceAccessible')
            ->willReturnMap([
                ['Test\Activity', $version, $requestType, true],
                ['Test\AnotherActivity', $version, $requestType, true],
                ['Test\NotAccessibleActivity', $version, $requestType, false],
            ]);

        $expected = [
            'activityActivities' => ['className' => 'Test\Activity', 'associationName' => 'activity'],
            'renamedActivities'  => ['className' => 'Test\AnotherActivity', 'associationName' => 'another']
        ];

        self::assertSame(
            $expected,
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            $expected,
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
    }

    public function testGetActivityAssociationsForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityManager->expects(self::never())
            ->method('getActivityAssociations');
        $this->resourcesProvider->expects(self::never())
            ->method('isResourceAccessible');

        self::assertSame(
            [],
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            [],
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
    }

    public function testGetActivityAssociationsForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->activityManager->expects(self::never())
            ->method('getActivityAssociations');
        $this->resourcesProvider->expects(self::never())
            ->method('isResourceAccessible');

        self::assertSame(
            [],
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            [],
            $this->activityAssociationProvider->getActivityAssociations($entityClass, $version, $requestType)
        );
    }

    public function testGetActivityTargetClasses(): void
    {
        $activityEntityClass = 'Test\Activity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->activityManager->expects(self::once())
            ->method('getActivityTargets')
            ->with($activityEntityClass)
            ->willReturn(['Test\Foo' => 'foo', 'Test\Bar' => 'bar', 'Test\Baz' => 'baz']);
        $this->resourcesProvider->expects(self::exactly(3))
            ->method('isResourceAccessible')
            ->willReturnMap([
                ['Test\Foo', $version, $requestType, true],
                ['Test\Bar', $version, $requestType, false],
                ['Test\Baz', $version, $requestType, true],
            ]);

        self::assertSame(
            ['Test\Baz', 'Test\Foo'],
            $this->activityAssociationProvider->getActivityTargetClasses($activityEntityClass, $version, $requestType)
        );
    }

    public function testGetActivityTargetClassesWhenNoAccessibleInApiActivities(): void
    {
        $activityEntityClass = 'Test\Activity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->activityManager->expects(self::once())
            ->method('getActivityTargets')
            ->with($activityEntityClass)
            ->willReturn(['Test\Foo' => 'foo', 'Test\Bar' => 'bar']);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceAccessible')
            ->willReturnMap([
                ['Test\Foo', $version, $requestType, false],
                ['Test\Bar', $version, $requestType, false],
            ]);

        self::assertSame(
            [],
            $this->activityAssociationProvider->getActivityTargetClasses($activityEntityClass, $version, $requestType)
        );
    }
}
