<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActivityListChainProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityListChainProvider */
    protected $provider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $routeHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var TestActivityProvider */
    protected $testActivityProvider;

    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->routeHelper = $this->createMock(EntityRoutingHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->testActivityProvider = new TestActivityProvider();

        $this->provider = new ActivityListChainProvider(
            $this->doctrineHelper,
            $this->configManager,
            $this->translator,
            $this->routeHelper,
            $this->tokenAccessor
        );
        $this->provider->addProvider($this->testActivityProvider);
    }

    public function testIsApplicableTarget()
    {
        $targetClassName   = TestActivityProvider::SUPPORTED_TARGET_CLASS_NAME;
        $activityClassName = TestActivityProvider::ACTIVITY_CLASS_NAME;

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(true);
        $this->configManager->expects($this->any())
            ->method('getId')
            ->with('entity', $targetClassName)
            ->willReturn(new EntityConfigId('entity', $targetClassName));

        $this->assertTrue(
            $this->provider->isApplicableTarget($targetClassName, $activityClassName)
        );
    }

    public function testIsApplicableTargetForNotSupportedTargetEntity()
    {
        $targetClassName   = 'Test\NotSupportedTargetEntity';
        $activityClassName = TestActivityProvider::ACTIVITY_CLASS_NAME;

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(true);
        $this->configManager->expects($this->any())
            ->method('getId')
            ->with('entity', $targetClassName)
            ->willReturn(new EntityConfigId('entity', $targetClassName));

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, $activityClassName)
        );
    }

    public function testIsApplicableTargetForNotRegisteredActivityEntity()
    {
        $targetClassName   = TestActivityProvider::SUPPORTED_TARGET_CLASS_NAME;
        $activityClassName = 'Test\NotRegisteredActivityEntity';

        $this->configManager->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, $activityClassName)
        );
    }

    public function testIsApplicableTargetForNotConfigurableTargetEntity()
    {
        $targetClassName   = 'Test\NotConfigurableTargetEntity';
        $activityClassName = TestActivityProvider::ACTIVITY_CLASS_NAME;

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($targetClassName)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getId');

        $this->assertFalse(
            $this->provider->isApplicableTarget($targetClassName, $activityClassName)
        );
    }

    public function testGetSupportedActivities()
    {
        $this->assertEquals(
            [TestActivityProvider::ACTIVITY_CLASS_NAME],
            $this->provider->getSupportedActivities()
        );
    }

    public function testIsSupportedEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue(TestActivityProvider::ACTIVITY_CLASS_NAME));
        $this->assertTrue($this->provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedEntityWrongEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue('\stdClass'));
        $this->assertFalse($this->provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedTargetEntity()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        $correctTarget,
                        $notCorrectTarget
                    ]
                )
            );

        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue($correctTarget->getClassName()));

        $this->assertTrue($this->provider->isSupportedTargetEntity($testEntity));
    }

    public function testIsSupportedTargetEntityWrongEntity()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        $correctTarget,
                        $notCorrectTarget
                    ]
                )
            );

        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue($notCorrectTarget->getClassName()));

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
        $correctTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        $correctTarget,
                        $notCorrectTarget
                    ]
                )
            );

        $this->assertEquals(['Acme\\DemoBundle\\Entity\\CorrectEntity'], $this->provider->getTargetEntityClasses());
    }

    public function getTargetEntityClassesOnEmptyTargetList()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue([])
            );

        $this->assertEquals([], $this->provider->getTargetEntityClasses());

        /**
         * Each subsequent execution of getTargetEntityClasses should NOT collect targets again
         */
        $this->provider->getTargetEntityClasses();
    }

    public function testGetProviderForEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn('Test\Entity');
        $this->assertEquals($this->testActivityProvider, $this->provider->getProviderForEntity($testEntity));
    }

    public function testGetActivityListOption()
    {
        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $configId = new EntityConfigId('entity', 'Test\Entity');
        $entityConfig = new Config($configId);
        $userConfig = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');
        $entityConfigProvider->expects($this->once())->method('getConfig')->willReturn($entityConfig);
        $this->translator->expects($this->once())->method('trans')->with('test_label')->willReturn('test_label');
        $this->routeHelper->expects($this->once())->method('getUrlSafeClassName')
            ->willReturn('Test_Entity');
        $this->configManager->expects($this->once())->method('getProvider')->willReturn($entityConfigProvider);

        $result = $this->provider->getActivityListOption($userConfig);
        $this->assertEquals(
            [
                'Test_Entity' => [
                    'icon'     => 'test_icon',
                    'label'    => 'test_label',
                    'template' => 'test_template.js.twig',
                    'has_comments' => true,
                ]
            ],
            $result
        );
    }

    public function testGetUpdatedActivityList()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();

        $activityEntity = new ActivityList();
        $repo->expects($this->once())->method('findOneBy')->willReturn($activityEntity);
        $em->expects($this->once())->method('getRepository')->willReturn($repo);

        $testEntity = new \stdClass();
        $testEntity->subject = 'testSubject';
        $testEntity->description = 'testDescription';
        $testEntity->owner = new User();
        $testEntity->updatedBy = new User();

        $this->testActivityProvider->setTargets([new \stdClass()]);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($entity) use ($testEntity) {
                    if ($entity === $testEntity) {
                        return 'Test\Entity';
                    }

                    return get_class($entity);
                }
            );

        $result = $this->provider->getUpdatedActivityList($testEntity, $em);
        $this->assertEquals('update', $result->getVerb());
        $this->assertEquals('testSubject', $result->getSubject());
    }

    public function testGetSupportedOwnerActivities()
    {
        $ownerClasses = $this->provider->getSupportedOwnerActivities();
        $this->assertCount(1, $ownerClasses);
        $this->assertEquals([TestActivityProvider::ACL_CLASS], $ownerClasses);
    }

    public function testIsSupportedOwnerEntity()
    {
        $testEntity = new \stdClass();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn(TestActivityProvider::ACL_CLASS);

        $this->assertTrue($this->provider->isSupportedEntity($testEntity));
    }

    public function testGetProviderForOwnerEntity()
    {
        $testEntity = new \stdClass();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(TestActivityProvider::ACL_CLASS);

        $this->assertEquals(
            $this->testActivityProvider,
            $this->provider->getProviderForOwnerEntity($testEntity)
        );
    }

    public function testGetProviderByOwnerClass()
    {
        $this->assertEquals(
            $this->testActivityProvider,
            $this->provider->getProviderByOwnerClass(TestActivityProvider::ACTIVITY_CLASS_NAME)
        );
    }
}
