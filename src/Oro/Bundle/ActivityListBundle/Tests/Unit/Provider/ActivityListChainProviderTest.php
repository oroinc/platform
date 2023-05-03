<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActivityBundle\Tests\Unit\Stub\TestTarget;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Factory\ActivityListFactory;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as UserConfig;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActivityListChainProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTIVITY_CLASS = EntityStub::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $routeHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var TestActivityProvider */
    private $testActivityProvider;

    /** @var ActivityListFactory|\PHPUnit\Framework\MockObject\MockObject  */
    private $activityListFactory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->routeHelper = $this->createMock(EntityRoutingHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->activityListFactory = $this->getMockBuilder(ActivityListFactory::class)
            ->onlyMethods(['createActivityList'])
            ->getMock();


        $this->testActivityProvider = new TestActivityProvider();
    }

    private function getActivityListChainProvider(string $testActivityAclClass = null): ActivityListChainProvider
    {
        $activityAclClasses = [];
        if ($testActivityAclClass) {
            $activityAclClasses[self::TEST_ACTIVITY_CLASS] = $testActivityAclClass;
        }

        $providerContainer = TestContainerBuilder::create()
            ->add(self::TEST_ACTIVITY_CLASS, $this->testActivityProvider)
            ->getContainer($this);

        return new ActivityListChainProvider(
            [self::TEST_ACTIVITY_CLASS],
            $activityAclClasses,
            $providerContainer,
            $this->doctrineHelper,
            $this->configManager,
            $this->translator,
            $this->routeHelper,
            $this->tokenAccessor,
            $this->activityListFactory
        );
    }

    public function testGetProviders()
    {
        $provider = $this->getActivityListChainProvider();
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS => $this->testActivityProvider],
            $provider->getProviders()
        );
    }

    public function testIsApplicableTarget()
    {
        $targetClassName = TestActivityProvider::SUPPORTED_TARGET_CLASS_NAME;

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(true);
        $this->configManager->expects($this->any())
            ->method('getId')
            ->with('entity', $targetClassName)
            ->willReturn(new EntityConfigId('entity', $targetClassName));

        $provider = $this->getActivityListChainProvider();
        $this->assertTrue(
            $provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testIsApplicableTargetForNotSupportedTargetEntity()
    {
        $targetClassName = 'Test\NotSupportedTargetEntity';

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(true);
        $this->configManager->expects($this->any())
            ->method('getId')
            ->with('entity', $targetClassName)
            ->willReturn(new EntityConfigId('entity', $targetClassName));

        $provider = $this->getActivityListChainProvider();
        $this->assertFalse(
            $provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testIsApplicableTargetForNotRegisteredActivityEntity()
    {
        $targetClassName = TestActivityProvider::SUPPORTED_TARGET_CLASS_NAME;
        $activityClassName = 'Test\NotRegisteredActivityEntity';

        $this->configManager->expects($this->never())
            ->method('hasConfig');

        $provider = $this->getActivityListChainProvider();
        $this->assertFalse(
            $provider->isApplicableTarget($targetClassName, $activityClassName)
        );
    }

    public function testIsApplicableTargetForNotConfigurableTargetEntity()
    {
        $targetClassName = 'Test\NotConfigurableTargetEntity';

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getId');

        $provider = $this->getActivityListChainProvider();
        $this->assertFalse(
            $provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetSupportedActivities()
    {
        $provider = $this->getActivityListChainProvider();
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS],
            $provider->getSupportedActivities()
        );
    }

    public function testIsSupportedEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn(self::TEST_ACTIVITY_CLASS);

        $provider = $this->getActivityListChainProvider();
        $this->assertTrue($provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedEntityWrongEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn(\stdClass::class);

        $provider = $this->getActivityListChainProvider();
        $this->assertFalse($provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedTargetEntity()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([$correctTarget, $notCorrectTarget]);

        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn($correctTarget->getClassName());

        $provider = $this->getActivityListChainProvider();
        $this->assertTrue($provider->isSupportedTargetEntity($testEntity));
    }

    public function testIsSupportedTargetEntityWrongEntity()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([$correctTarget, $notCorrectTarget]);

        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn($notCorrectTarget->getClassName());

        $provider = $this->getActivityListChainProvider();
        $this->assertFalse($provider->isSupportedTargetEntity($testEntity));
    }

    public function testGetSubject()
    {
        $testEntity = new \stdClass();
        $testEntity->subject = 'test';

        $provider = $this->getActivityListChainProvider();
        $this->assertEquals('test', $provider->getSubject($testEntity));
    }

    public function testGetDescription()
    {
        $testEntity = new \stdClass();
        $testEntity->description = 'test';

        $provider = $this->getActivityListChainProvider();
        $this->assertEquals('test', $provider->getDescription($testEntity));
    }

    public function testGetEmptySubject()
    {
        $testEntity = new TestTarget(1);

        $provider = $this->getActivityListChainProvider();
        $this->assertNull($provider->getSubject($testEntity));
    }

    public function testGetTargetEntityClasses()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([$correctTarget, $notCorrectTarget]);

        $provider = $this->getActivityListChainProvider();
        $this->assertEquals(['Acme\DemoBundle\Entity\CorrectEntity'], $provider->getTargetEntityClasses());
    }

    public function getTargetEntityClassesOnEmptyTargetList()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([]);

        $provider = $this->getActivityListChainProvider();
        $this->assertEquals([], $provider->getTargetEntityClasses());

        /**
         * Each subsequent execution of getTargetEntityClasses should NOT collect targets again
         */
        $provider->getTargetEntityClasses();
    }

    public function testGetActivityListOption()
    {
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $configId = new EntityConfigId('entity', self::TEST_ACTIVITY_CLASS);
        $entityConfig = new Config($configId);
        $userConfig = $this->createMock(UserConfig::class);

        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($entityConfig);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test_label')
            ->willReturn('test_label');
        $this->routeHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->willReturn('Test_Entity');
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->willReturn($entityConfigProvider);

        $provider = $this->getActivityListChainProvider();
        $result = $provider->getActivityListOption($userConfig);
        $this->assertEquals(
            [
                'Test_Entity' => [
                    'icon'         => 'test_icon',
                    'label'        => 'test_label',
                    'template'     => 'test_template.js.twig',
                    'has_comments' => true,
                ]
            ],
            $result
        );
    }

    public function testGetUpdatedActivityList()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(ActivityListRepository::class);

        $activityEntity = $this->getMockBuilder(ActivityList::class)
            ->addMethods(['getActivityListTargets', 'supportActivityListTarget'])
            ->getMock();
        $activityEntity->expects($this->once())->method('getActivityListTargets')->willReturn([]);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($activityEntity);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $testEntity = new \stdClass();
        $testEntity->subject = 'testSubject';
        $testEntity->description = 'testDescription';
        $testEntity->owner = new User();
        $testEntity->updatedBy = new User();

        $this->testActivityProvider->setTargets([new \stdClass()]);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) use ($testEntity) {
                if ($entity === $testEntity) {
                    return self::TEST_ACTIVITY_CLASS;
                }

                return get_class($entity);
            });

        $provider = $this->getActivityListChainProvider();
        $result = $provider->getUpdatedActivityList($testEntity, $em);
        $this->assertEquals('update', $result->getVerb());
        $this->assertEquals('testSubject', $result->getSubject());
    }


    public function testGetNewActivityList()
    {
        $testEntity = new \stdClass();
        $testEntity->id = 123;
        $testEntity->subject = 'testSubject';
        $testEntity->description = 'testDescription';
        $testEntity->owner = new User();
        $testEntity->updatedBy = new User();

        $activityEntity = $this->getMockBuilder(ActivityList::class)
            ->addMethods(['getActivityListTargets', 'supportActivityListTarget'])
            ->getMock();
        $activityEntity->expects($this->once())->method('supportActivityListTarget');
        $activityEntity->expects($this->never())->method('getActivityListTargets');
        $targetEntity = new \stdClass();
        $this->testActivityProvider->setTargets([$targetEntity]);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityClass')
            ->withConsecutive(
                [self::identicalTo($testEntity)],
                [self::identicalTo($testEntity)],
                [self::identicalTo($targetEntity)]
            )
            ->willReturnCallback(function ($entity) use ($testEntity) {
                return $entity === $testEntity
                    ? self::TEST_ACTIVITY_CLASS
                    : get_class($entity);
            });
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($testEntity))
            ->willReturn($testEntity->id);
        $this->activityListFactory->expects($this->once())
            ->method('createActivityList')
            ->willReturn($activityEntity);

        $provider = $this->getActivityListChainProvider();
        $result = $provider->getNewActivityList($testEntity);
        $this->assertEquals(ActivityList::VERB_CREATE, $result->getVerb());
        $this->assertEquals($testEntity->subject, $result->getSubject());
        $this->assertEquals($testEntity->description, $result->getDescription());
        $this->assertSame($testEntity->owner, $result->getOwner());
        $this->assertSame($testEntity->updatedBy, $result->getUpdatedBy());
        $this->assertEquals(self::TEST_ACTIVITY_CLASS, $result->getRelatedActivityClass());
        $this->assertEquals($testEntity->id, $result->getRelatedActivityId());
    }

    public function testGetSupportedOwnerActivities()
    {
        $provider = $this->getActivityListChainProvider();
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS],
            $provider->getSupportedOwnerActivities()
        );
        $this->assertEquals(
            self::TEST_ACTIVITY_CLASS,
            $provider->getSupportedOwnerActivity(self::TEST_ACTIVITY_CLASS)
        );

        $testActivityAclClass = 'Test\AclClass';
        $provider = $this->getActivityListChainProvider($testActivityAclClass);
        $this->assertEquals(
            [$testActivityAclClass],
            $provider->getSupportedOwnerActivities()
        );
        $this->assertEquals(
            $testActivityAclClass,
            $provider->getSupportedOwnerActivity(self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testIsSupportedOwnerEntity()
    {
        $testEntityClass = self::TEST_ACTIVITY_CLASS;
        $testEntity = new $testEntityClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($testEntity))
            ->willReturn($testEntityClass);

        $provider = $this->getActivityListChainProvider();
        $this->assertTrue($provider->isSupportedEntity($testEntity));
    }

    public function testGetProviderByClassWhenProvidersAreNotInitializedYet()
    {
        $provider = $this->getActivityListChainProvider();
        $this->assertSame(
            $this->testActivityProvider,
            $provider->getProviderByClass(self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetProviderByClassWhenProvidersAlreadyInitialized()
    {
        $provider = $this->getActivityListChainProvider();

        // initialize providers
        $provider->getProviders();

        $this->assertSame(
            $this->testActivityProvider,
            $provider->getProviderByClass(self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetProviderForEntity()
    {
        $testEntityClass = self::TEST_ACTIVITY_CLASS;
        $testEntity = new $testEntityClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($testEntity))
            ->willReturn($testEntityClass);

        $provider = $this->getActivityListChainProvider();
        $this->assertSame(
            $this->testActivityProvider,
            $provider->getProviderForEntity($testEntity)
        );
    }

    public function testGetProviderByOwnerClass()
    {
        $provider = $this->getActivityListChainProvider();
        $this->assertSame(
            $this->testActivityProvider,
            $provider->getProviderByOwnerClass(self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetProviderForOwnerEntity()
    {
        $testEntityClass = self::TEST_ACTIVITY_CLASS;
        $testEntity = new $testEntityClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($testEntity))
            ->willReturn($testEntityClass);

        $provider = $this->getActivityListChainProvider();
        $this->assertSame(
            $this->testActivityProvider,
            $provider->getProviderForOwnerEntity($testEntity)
        );
    }

    public function testGetActivityListEntitiesByActivityEntityForEntityWithoutId()
    {
        $testEntity = new \stdClass();
        $testEntity->subject = 'testSubject';
        $testEntity->description = 'testDescription';
        $testEntity->owner = new User();
        $testEntity->updatedBy = new User();

        $this->testActivityProvider->setTargets([new \stdClass()]);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) use ($testEntity) {
                if ($entity === $testEntity) {
                    return self::TEST_ACTIVITY_CLASS;
                }

                return get_class($entity);
            });

        $provider = $this->getActivityListChainProvider();
        $result = $provider->getActivityListEntitiesByActivityEntity($testEntity);
        $this->assertNull($result);
    }
}
