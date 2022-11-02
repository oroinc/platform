<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Tests\Unit\Stub\TestTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Component\EntitySerializer\EntitySerializer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActivityContextApiEntityManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityAliasResolver;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    /** @var ActivityContextApiEntityManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->manager = new ActivityContextApiEntityManager(
            $this->createMock(ObjectManager::class),
            $this->configManager,
            $this->router,
            $this->entityAliasResolver,
            $this->entityNameResolver,
            $this->featureChecker,
            $this->authorizationChecker
        );

        $this->manager->setDoctrineHelper($this->doctrineHelper);

        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->manager->setEntityClassNameHelper($this->entityClassNameHelper);

        $entitySerializer = $this->createMock(EntitySerializer::class);
        $this->manager->setEntitySerializer($entitySerializer);
    }

    /**
     * @dataProvider getActivityContextWhenNotActivityDataProvider
     */
    public function testGetActivityContextWhenNotActivity(?object $entity): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects(self::never())
            ->method('getProvider');

        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals([], $result);
    }

    public function getActivityContextWhenNotActivityDataProvider(): array
    {
        return [
            [null],
            [new \stdClass],
        ];
    }

    public function testGetActivityContextWhenNotGranted(): void
    {
        $target = new TestTarget(1);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn($this->getActivity([$target]));

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($this->createMock(ConfigProvider::class));

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $target)
            ->willReturn(false);

        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals([], $result);
    }

    private function getActivity(array $targets): ActivityInterface
    {
        $activity = $this->createMock(TestActivity::class);
        $activity->expects(self::any())
            ->method('getActivityTargets')
            ->willReturn($targets);

        return $activity;
    }

    public function testGetActivityContextWhenFeatureNotEnabled(): void
    {
        $target = new TestTarget(1);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn($this->getActivity([$target]));

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($this->createMock(ConfigProvider::class));

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $target)
            ->willReturn(true);

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(get_class($target), 'entities')
            ->willReturn(false);

        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals([], $result);
    }

    /**
     * @dataProvider getActivityContextDataProvider
     */
    public function testGetActivityContext(array $targets, array $expectedResult): void
    {
        $this->mockCheckers($targets);

        $this->mockBuildItem();

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::atLeastOnce())
            ->method('dispatch')
            ->with(self::isInstanceOf(PrepareContextTitleEvent::class), PrepareContextTitleEvent::EVENT_NAME);

        $this->manager->setEventDispatcher($eventDispatcher);
        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals($expectedResult, $result);
    }

    private function mockCheckers(array $targets): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturn($this->getActivity($targets));

        $this->authorizationChecker->expects(self::atLeastOnce())
            ->method('isGranted')
            ->willReturn(true);

        $this->featureChecker->expects(self::atLeastOnce())
            ->method('isResourceEnabled')
            ->willReturn(true);
    }

    private function mockBuildItem(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($configProvider);
        $configProvider->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects(self::atLeastOnce())
            ->method('get')
            ->with('icon')
            ->willReturn('sample-icon');

        $this->entityClassNameHelper->expects(self::atLeastOnce())
            ->method('getUrlSafeClassName')
            ->willReturn('sample-safe-name');

        $this->entityNameResolver->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturnCallback(function ($target) {
                return 'sample-name-' . $target->getId();
            });

        $this->entityAliasResolver->expects(self::atLeastOnce())
            ->method('getPluralAlias')
            ->willReturn('sample-alias-plural');

        $this->mockGetContextLink();
    }

    private function mockGetContextLink(): void
    {
        $route = 'sample-route';
        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routeView = $route;

        $this->configManager->expects(self::atLeastOnce())
            ->method('getEntityMetadata')
            ->willReturnOnConsecutiveCalls($entityMetadata, null);

        $this->router->expects(self::atLeastOnce())
            ->method('generate')
            ->with($route)
            ->willReturn('sample-url');
    }

    public function getActivityContextDataProvider(): array
    {
        return [
            [
                'targets' => [new TestTarget(2), new TestTarget(1)],
                'expectedResult' => [
                    [
                        'title' => 'sample-name-1',
                        'activityClassAlias' => 'sample-alias-plural',
                        'entityId' => 1,
                        'targetId' => 1,
                        'targetClassName' => 'sample-safe-name',
                        'icon' => 'sample-icon',
                        'link' => null,
                    ],
                    [
                        'title' => 'sample-name-2',
                        'activityClassAlias' => 'sample-alias-plural',
                        'entityId' => 1,
                        'targetId' => 2,
                        'targetClassName' => 'sample-safe-name',
                        'icon' => 'sample-icon',
                        'link' => 'sample-url',
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider getActivityContextDataProvider
     */
    public function testGetActivityContextWhenNoEventDispatcher(array $targets, array $expectedResult): void
    {
        $this->mockCheckers($targets);

        $this->mockBuildItem();

        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals($expectedResult, $result);
    }
}
