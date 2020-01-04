<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestTarget;
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

    /** @var ActivityListChainProvider */
    private $provider;

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

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->routeHelper = $this->createMock(EntityRoutingHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->testActivityProvider = new TestActivityProvider();

        $providerContainer = TestContainerBuilder::create()
            ->add(self::TEST_ACTIVITY_CLASS, $this->testActivityProvider)
            ->getContainer($this);

        $this->provider = new ActivityListChainProvider(
            [self::TEST_ACTIVITY_CLASS],
            $providerContainer,
            $this->doctrineHelper,
            $this->configManager,
            $this->translator,
            $this->routeHelper,
            $this->tokenAccessor
        );
    }

    public function testGetProviders()
    {
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS => $this->testActivityProvider],
            $this->provider->getProviders()
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

        $this->assertTrue(
            $this->provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
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

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testIsApplicableTargetForNotRegisteredActivityEntity()
    {
        $targetClassName = TestActivityProvider::SUPPORTED_TARGET_CLASS_NAME;
        $activityClassName = 'Test\NotRegisteredActivityEntity';

        $this->configManager->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, $activityClassName)
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

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetSupportedActivities()
    {
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS],
            $this->provider->getSupportedActivities()
        );
    }

    public function testIsSupportedEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn(self::TEST_ACTIVITY_CLASS);
        $this->assertTrue($this->provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedEntityWrongEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn('\stdClass');
        $this->assertFalse($this->provider->isSupportedEntity($testEntity));
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

        $this->assertTrue($this->provider->isSupportedTargetEntity($testEntity));
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

        $this->assertFalse($this->provider->isSupportedTargetEntity($testEntity));
    }

    public function testGetSubject()
    {
        $testEntity = new \stdClass();
        $testEntity->subject = 'test';
        $this->assertEquals('test', $this->provider->getSubject($testEntity));
    }

    public function testGetDescription()
    {
        $testEntity = new \stdClass();
        $testEntity->description = 'test';
        $this->assertEquals('test', $this->provider->getDescription($testEntity));
    }

    public function testGetEmptySubject()
    {
        $testEntity = new TestTarget(1);
        $this->assertNull($this->provider->getSubject($testEntity));
    }

    public function testGetTargetEntityClasses()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\DemoBundle\Entity\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([$correctTarget, $notCorrectTarget]);

        $this->assertEquals(['Acme\DemoBundle\Entity\CorrectEntity'], $this->provider->getTargetEntityClasses());
    }

    public function getTargetEntityClassesOnEmptyTargetList()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->willReturn([]);

        $this->assertEquals([], $this->provider->getTargetEntityClasses());

        /**
         * Each subsequent execution of getTargetEntityClasses should NOT collect targets again
         */
        $this->provider->getTargetEntityClasses();
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

        $result = $this->provider->getActivityListOption($userConfig);
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
        $em = $this->createMock(EntityManager::class);
        $repo = $this->createMock(ActivityListRepository::class);

        $activityEntity = new ActivityList();
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

        $result = $this->provider->getUpdatedActivityList($testEntity, $em);
        $this->assertEquals('update', $result->getVerb());
        $this->assertEquals('testSubject', $result->getSubject());
    }

    public function testGetSupportedOwnerActivities()
    {
        $this->assertEquals(
            [self::TEST_ACTIVITY_CLASS],
            $this->provider->getSupportedOwnerActivities()
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

        $this->assertTrue($this->provider->isSupportedEntity($testEntity));
    }

    public function testGetProviderByClassWhenProvidersAreNotInitializedYet()
    {
        $this->assertSame(
            $this->testActivityProvider,
            $this->provider->getProviderByClass(self::TEST_ACTIVITY_CLASS)
        );
    }

    public function testGetProviderByClassWhenProvidersAlreadyInitialized()
    {
        // initialize providers
        $this->provider->getProviders();

        $this->assertSame(
            $this->testActivityProvider,
            $this->provider->getProviderByClass(self::TEST_ACTIVITY_CLASS)
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

        $this->assertSame(
            $this->testActivityProvider,
            $this->provider->getProviderForEntity($testEntity)
        );
    }

    public function testGetProviderByOwnerClass()
    {
        $this->assertSame(
            $this->testActivityProvider,
            $this->provider->getProviderByOwnerClass(self::TEST_ACTIVITY_CLASS)
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

        $this->assertSame(
            $this->testActivityProvider,
            $this->provider->getProviderForOwnerEntity($testEntity)
        );
    }
}
