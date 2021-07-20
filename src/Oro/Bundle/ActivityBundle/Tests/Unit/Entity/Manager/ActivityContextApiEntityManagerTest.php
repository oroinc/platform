<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
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

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject $entityClassNameHelper */
    private $entityClassNameHelper;

    /** @var ActivityContextApiEntityManager */
    private $manager;

    protected function setUp(): void
    {
        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $objectManager = $this->createMock(ObjectManager::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->manager = new ActivityContextApiEntityManager(
            $objectManager,
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

        /** @var EntitySerializer|\PHPUnit\Framework\MockObject\MockObject $entitySerializer */
        $entitySerializer = $this->createMock(EntitySerializer::class);
        $this->manager->setEntitySerializer($entitySerializer);
    }

    /**
     * @dataProvider getActivityContextWhenNotActivityDataProvider
     *
     * @param null|object $entity
     */
    public function testGetActivityContextWhenNotActivity($entity): void
    {
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager
            ->expects(self::never())
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
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($activity = $this->getActivity([$target = $this->getTarget(1)]));

        $this->configManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($configProvider = $this->createMock(ConfigProvider::class));

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $target)
            ->willReturn(false);

        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals([], $result);
    }

    private function getActivity(array $targets): ActivityInterface
    {
        $activity = $this->createMock(TestActivity::class);
        $activity
            ->method('getActivityTargets')
            ->willReturn($targets);

        return $activity;
    }

    /**
     * @param int $id
     *
     * @return object
     */
    private function getTarget(int $id)
    {
        return new class($id) {
            protected $id;

            public function __construct(int $id)
            {
                $this->id = $id;
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }

    public function testGetActivityContextWhenFeatureNotEnabled(): void
    {
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($activity = $this->getActivity([$target = $this->getTarget(1)]));

        $this->configManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($configProvider = $this->createMock(ConfigProvider::class));

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $target)
            ->willReturn(true);

        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with(\get_class($target), 'entities')
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

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::atLeastOnce())
            ->method('dispatch')
            ->with(self::isInstanceOf(PrepareContextTitleEvent::class), PrepareContextTitleEvent::EVENT_NAME);

        $this->manager->setEventDispatcher($eventDispatcher);
        $result = $this->manager->getActivityContext(TestActivity::class, 1);

        self::assertEquals($expectedResult, $result);
    }

    private function mockCheckers(array $targets): void
    {
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($activity = $this->getActivity($targets));

        $this->authorizationChecker
            ->expects(self::atLeastOnce())
            ->method('isGranted')
            ->willReturn(true);

        $this->featureChecker
            ->expects(self::atLeastOnce())
            ->method('isResourceEnabled')
            ->willReturn(true);
    }

    private function mockBuildItem(): void
    {
        $this->configManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($configProvider = $this->createMock(ConfigProvider::class));

        $configProvider
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('icon')
            ->willReturn($icon = 'sample-icon');

        $this->entityClassNameHelper
            ->expects(self::atLeastOnce())
            ->method('getUrlSafeClassName')
            ->willReturn($urlSafeClassName = 'sample-safe-name');

        $this->entityNameResolver
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturnCallback(function ($target) {
                return 'sample-name-' . $target->getId();
            });

        $this->entityAliasResolver
            ->expects(self::atLeastOnce())
            ->method('getPluralAlias')
            ->willReturn('sample-alias-plural');

        $this->mockGetContextLink();
    }

    private function mockGetContextLink(): void
    {
        $this->configManager
            ->expects(self::atLeastOnce())
            ->method('getEntityMetadata')
            ->willReturnOnConsecutiveCalls(
                $metadata = $this->createMock(EntityMetadata::class),
                null
            );

        $metadata
            ->expects(self::atLeastOnce())
            ->method('getRoute')
            ->with('view', true)
            ->willReturn($route = 'sample-route');

        $this->router
            ->expects(self::atLeastOnce())
            ->method('generate')
            ->with($route)
            ->willReturn($link = 'sample-url');
    }

    public function getActivityContextDataProvider(): array
    {
        return [
            [
                'targets' => [$this->getTarget(2), $this->getTarget(1)],
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
