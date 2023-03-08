<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity;
use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Another;
use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActivityManagerTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $groupingConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var AssociationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $associationManager;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ActivityManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $doctrine->expects($this->any())
            ->method('getAliasNamespace')
            ->willReturnMap([
                ['Test', 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity']
            ]);

        $this->activityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->groupingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $this->manager = new ActivityManager(
            new DoctrineHelper($doctrine),
            new EntityClassResolver($doctrine),
            $this->activityConfigProvider,
            $this->groupingConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->associationManager,
            $featureChecker
        );
        $this->manager->setEventDispatcher($this->eventDispatcher);
    }

    public function testHasActivityAssociations()
    {
        $targetEntityClass = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', ['Test\Entity1', 'Test\Entity2']);

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(true);
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->willReturn($targetEntityActivityConfig);

        $this->assertTrue(
            $this->manager->hasActivityAssociations($targetEntityClass)
        );
    }

    public function testHasActivityAssociationsForNoActivities()
    {
        $targetEntityClass = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(true);
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->willReturn($targetEntityActivityConfig);

        $this->assertFalse(
            $this->manager->hasActivityAssociations($targetEntityClass)
        );
    }

    public function testHasActivityAssociationsForNonConfigurableEntity()
    {
        $targetEntityClass = 'Test\Entity';

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(false);

        $this->assertFalse(
            $this->manager->hasActivityAssociations($targetEntityClass)
        );
    }

    public function testHasActivityAssociation()
    {
        $activityEntityClass = 'Test\Activity';
        $targetEntityClass = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', ['Test\OtherActivity', $activityEntityClass]);

        $this->activityConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(true);
        $this->activityConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with($targetEntityClass)
            ->willReturn($targetEntityActivityConfig);

        $this->assertTrue(
            $this->manager->hasActivityAssociation($targetEntityClass, $activityEntityClass)
        );
        $this->assertFalse(
            $this->manager->hasActivityAssociation($targetEntityClass, 'Test\UnsupportedActivity')
        );
    }

    public function testHasActivityAssociationForNoActivities()
    {
        $activityEntityClass = 'Test\Activity';
        $targetEntityClass = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(true);
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->willReturn($targetEntityActivityConfig);

        $this->assertFalse(
            $this->manager->hasActivityAssociation($targetEntityClass, $activityEntityClass)
        );
    }

    public function testHasActivityAssociationForNonConfigurableEntity()
    {
        $activityEntityClass = 'Test\Activity';
        $targetEntityClass = 'Test\Entity';

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->willReturn(false);

        $this->assertFalse(
            $this->manager->hasActivityAssociation($targetEntityClass, $activityEntityClass)
        );
    }

    public function testAddActivityTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn($activityEntity);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything(), Events::ADD_ACTIVITY);

        $this->assertTrue(
            $this->manager->addActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testAddActivityTargetForAlreadyAddedTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testAddActivityTargetForNotSupportedTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->never())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity));
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testAddActivityTargetForNullTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $activityEntity->expects($this->never())
            ->method('supportActivityTarget');
        $activityEntity->expects($this->never())
            ->method('hasActivityTarget');
        $activityEntity->expects($this->never())
            ->method('addActivityTarget');

        $this->assertFalse(
            $this->manager->addActivityTarget($activityEntity, null)
        );
    }

    public function testAddActivityTargets()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn($activityEntity);

        $this->assertTrue(
            $this->manager->addActivityTargets($activityEntity, [$targetEntity])
        );
    }

    public function testAddActivityTargetsForAlreadyAddedTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTargets($activityEntity, [$targetEntity])
        );
    }

    public function testAddActivityTargetsForNotSupportedTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->never())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity));
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTargets($activityEntity, [$targetEntity])
        );
    }

    public function testRemoveActivityTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn($activityEntity);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything(), Events::REMOVE_ACTIVITY);

        $this->assertTrue(
            $this->manager->removeActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testRemoveActivityTargetForNotExistingTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(true);
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->removeActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testRemoveActivityTargetForNotSupportedTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);
        $targetEntity = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->willReturn(false);
        $activityEntity->expects($this->never())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity));
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->removeActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testRemoveActivityTargetForNullTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $activityEntity->expects($this->never())
            ->method('supportActivityTarget');
        $activityEntity->expects($this->never())
            ->method('hasActivityTarget');
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget');

        $this->assertFalse(
            $this->manager->removeActivityTarget($activityEntity, null)
        );
    }

    public function testReplaceActivityTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->willReturnMap([
                [get_class($oldTargetEntity), true],
                [get_class($newTargetEntity), true],
            ]);
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->willReturnMap([
                [$oldTargetEntity, true],
                [$newTargetEntity, false],
            ]);
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($oldTargetEntity))
            ->willReturn($activityEntity);
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($newTargetEntity))
            ->willReturn($activityEntity);

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNoAssociationWithOldTarget()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->willReturnMap([
                [get_class($oldTargetEntity), true],
                [get_class($newTargetEntity), true],
            ]);
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->willReturnMap([
                [$oldTargetEntity, false],
                [$newTargetEntity, false],
            ]);
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget');
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($newTargetEntity))
            ->willReturn($activityEntity);

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNewTargetAlreadyExist()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->willReturnMap([
                [get_class($oldTargetEntity), true],
                [get_class($newTargetEntity), true],
            ]);
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->willReturnMap([
                [$oldTargetEntity, true],
                [$newTargetEntity, true],
            ]);
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($oldTargetEntity))
            ->willReturn($activityEntity);
        $activityEntity->expects($this->never())
            ->method('addActivityTarget');

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNoChanges()
    {
        $activityEntity = $this->createMock(ActivityInterface::class);

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->willReturnMap([
                [get_class($oldTargetEntity), true],
                [get_class($newTargetEntity), true],
            ]);
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->willReturnMap([
                [$oldTargetEntity, false],
                [$newTargetEntity, true],
            ]);
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget');
        $activityEntity->expects($this->never())
            ->method('addActivityTarget');

        $this->assertFalse(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testGetActivityAssociations()
    {
        $targetEntityClass = 'Test\Entity';
        $activity1Class = 'Test\Activity1';
        $activity2Class = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

        $activityAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClass,
            ActivityScope::ASSOCIATION_KIND
        );
        $activityAssociation1Config = new Config(
            new FieldConfigId('extend', $activity1Class, $activityAssociationName)
        );
        $activityAssociation1Config->set('is_extend', true);
        $activityAssociation1Config->set('state', ExtendScope::STATE_ACTIVE);
        $activityAssociation2Config = new Config(
            new FieldConfigId('extend', $activity2Class, $activityAssociationName)
        );
        $activityAssociation2Config->set('is_extend', true);
        $activityAssociation2Config->set('state', ExtendScope::STATE_ACTIVE);

        $activity1EntityConfig = new Config(new EntityConfigId('entity', $activity1Class));
        $activity1EntityConfig->set('plural_label', 'lbl.activity1');
        $activity1ActivityConfig = new Config(new EntityConfigId('activity', $activity1Class));
        $activity1ActivityConfig->set('route', 'route1');
        $activity1ActivityConfig->set('acl', 'acl1');

        $activity2EntityConfig = new Config(new EntityConfigId('entity', $activity2Class));
        $activity2EntityConfig->set('plural_label', 'lbl.activity2');
        $activity2ActivityConfig = new Config(new EntityConfigId('activity', $activity2Class));
        $activity2ActivityConfig->set('route', 'route2');
        $activity2ActivityConfig->set('priority', 100);

        $this->extendConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [$activity1Class, $activityAssociationName, true],
                [$activity2Class, $activityAssociationName, true],
            ]);
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$activity1Class, $activityAssociationName, $activityAssociation1Config],
                [$activity2Class, $activityAssociationName, $activityAssociation2Config],
            ]);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$activity1Class, null, $activity1EntityConfig],
                [$activity2Class, null, $activity2EntityConfig],
            ]);
        $this->activityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$targetEntityClass, null, $targetEntityActivityConfig],
                [$activity1Class, null, $activity1ActivityConfig],
                [$activity2Class, null, $activity2ActivityConfig],
            ]);

        $this->assertEquals(
            [
                [
                    'className'       => 'Test\Activity1',
                    'associationName' => 'entity_1f801d4a',
                    'label'           => 'lbl.activity1',
                    'route'           => 'route1',
                    'acl'             => 'acl1',
                ],
                [
                    'className'       => 'Test\Activity2',
                    'associationName' => 'entity_1f801d4a',
                    'label'           => 'lbl.activity2',
                    'route'           => 'route2',
                    'priority'        => 100,
                ],
            ],
            $this->manager->getActivityAssociations($targetEntityClass)
        );
    }

    /**
     * Test that activity is not returned if an activity is enabled on UI but schema update is not performed yet
     */
    public function testGetActivityAssociationsNoSchemaUpdate()
    {
        $targetEntityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activityClass]);

        $activityAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClass,
            ActivityScope::ASSOCIATION_KIND
        );
        $activityAssociationConfig = new Config(
            new FieldConfigId('extend', $activityClass, $activityAssociationName)
        );
        $activityAssociationConfig->set('is_extend', true);
        $activityAssociationConfig->set('state', ExtendScope::STATE_NEW);

        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass, null)
            ->willReturn($targetEntityActivityConfig);
        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($activityClass, $activityAssociationName)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($activityClass, $activityAssociationName)
            ->willReturn($activityAssociationConfig);
        $this->entityConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(
            [],
            $this->manager->getActivityAssociations($targetEntityClass)
        );
    }

    public function testGetActivityActions()
    {
        $targetEntityClass = 'Test\Entity';
        $activity1Class = 'Test\Activity1';
        $activity2Class = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

        $activityAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClass,
            ActivityScope::ASSOCIATION_KIND
        );
        $activityAssociation1Config = new Config(
            new FieldConfigId('extend', $activity1Class, $activityAssociationName)
        );
        $activityAssociation1Config->set('is_extend', true);
        $activityAssociation1Config->set('state', ExtendScope::STATE_ACTIVE);
        $activityAssociation2Config = new Config(
            new FieldConfigId('extend', $activity2Class, $activityAssociationName)
        );
        $activityAssociation2Config->set('is_extend', true);
        $activityAssociation2Config->set('state', ExtendScope::STATE_ACTIVE);

        $activity1EntityConfig = new Config(new EntityConfigId('entity', $activity1Class));
        $activity1EntityConfig->set('plural_label', 'lbl.activity1');
        $activity1ActivityConfig = new Config(new EntityConfigId('activity', $activity1Class));
        $activity1ActivityConfig->set('action_button_widget', 'button_widget1');
        $activity1ActivityConfig->set('action_link_widget', 'link_widget1');

        $activity2EntityConfig = new Config(new EntityConfigId('entity', $activity2Class));
        $activity2EntityConfig->set('plural_label', 'lbl.activity2');
        $activity2ActivityConfig = new Config(new EntityConfigId('activity', $activity2Class));
        $activity2ActivityConfig->set('action_button_widget', 'button_widget2');
        $activity2ActivityConfig->set('action_link_widget', 'link_widget2');
        $activity2ActivityConfig->set('priority', 100);

        $this->extendConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [$activity1Class, $activityAssociationName, true],
                [$activity2Class, $activityAssociationName, true],
            ]);
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$activity1Class, $activityAssociationName, $activityAssociation1Config],
                [$activity2Class, $activityAssociationName, $activityAssociation2Config],
            ]);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$activity1Class, null, $activity1EntityConfig],
                [$activity2Class, null, $activity2EntityConfig],
            ]);
        $this->activityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$targetEntityClass, null, $targetEntityActivityConfig],
                [$activity1Class, null, $activity1ActivityConfig],
                [$activity2Class, null, $activity2ActivityConfig],
            ]);

        $this->assertEquals(
            [
                [
                    'className'       => 'Test\Activity1',
                    'associationName' => 'entity_1f801d4a',
                    'button_widget'   => 'button_widget1',
                    'link_widget'     => 'link_widget1',
                ],
                [
                    'className'       => 'Test\Activity2',
                    'associationName' => 'entity_1f801d4a',
                    'button_widget'   => 'button_widget2',
                    'link_widget'     => 'link_widget2',
                    'priority'        => 100,
                ],
            ],
            $this->manager->getActivityActions($targetEntityClass)
        );
    }

    /**
     * Test that activity action is not returned if an activity is enabled on UI but schema update is not performed yet
     */
    public function testGetActivityActionsNoSchemaUpdate()
    {
        $targetEntityClass = 'Test\Entity';
        $activityClass = 'Test\Activity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activityClass]);

        $activityAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClass,
            ActivityScope::ASSOCIATION_KIND
        );
        $activityAssociationConfig = new Config(
            new FieldConfigId('extend', $activityClass, $activityAssociationName)
        );
        $activityAssociationConfig->set('is_extend', true);
        $activityAssociationConfig->set('state', ExtendScope::STATE_NEW);

        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass, null)
            ->willReturn($targetEntityActivityConfig);
        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($activityClass, $activityAssociationName)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($activityClass, $activityAssociationName)
            ->willReturn($activityAssociationConfig);
        $this->entityConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(
            [],
            $this->manager->getActivityActions($targetEntityClass)
        );
    }

    public function testAddFilterByTargetEntity()
    {
        $targetEntityClass = Target::class;
        $targetEntityId = 123;

        $qb = $this->em->getRepository(Activity::class)->createQueryBuilder('activity')
            ->select('activity');

        $this->manager->addFilterByTargetEntity($qb, $targetEntityClass, $targetEntityId);

        $this->assertEquals(
            'SELECT activity'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity activity'
            . ' WHERE activity.id IN('
            . 'SELECT filterActivityEntity.id'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity filterActivityEntity'
            . ' INNER JOIN filterActivityEntity.target_cb0fccb1 filterTargetEntity'
            . ' WHERE filterTargetEntity.id = :targetEntityId)',
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals(
            $targetEntityId,
            $qb->getParameter('targetEntityId')->getValue()
        );
    }

    public function testAddFilterByTargetEntityWithSeveralRootEntities()
    {
        $targetEntityClass = Target::class;
        $targetEntityId = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from(Activity::class, 'activity')
            ->from(Another::class, 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity(
            $qb,
            $targetEntityClass,
            $targetEntityId,
            Activity::class
        );

        $this->assertEquals(
            'SELECT activity, another'
            . ' FROM ' . Activity::class . ' activity, ' . Another::class . ' another'
            . ' WHERE another.id = activity.id AND activity.id IN('
            . 'SELECT filterActivityEntity.id'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity filterActivityEntity'
            . ' INNER JOIN filterActivityEntity.target_cb0fccb1 filterTargetEntity'
            . ' WHERE filterTargetEntity.id = :targetEntityId)',
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals(
            $targetEntityId,
            $qb->getParameter('targetEntityId')->getValue()
        );
    }

    public function testAddFilterByTargetEntityWithEmptyQuery()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The query must have at least one root entity.');

        $targetEntityClass = Target::class;
        $targetEntityId = 123;

        $qb = $this->em->createQueryBuilder();

        $this->manager->addFilterByTargetEntity($qb, $targetEntityClass, $targetEntityId);
    }

    public function testAddFilterByTargetEntityWithSeveralRootEntitiesButWithoutActivityEntityClassSpecified()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The $activityEntityClass must be specified if the query has several root entities.'
        );

        $targetEntityClass = Target::class;
        $targetEntityId = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from(Activity::class, 'activity')
            ->from(Another::class, 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity($qb, $targetEntityClass, $targetEntityId);
    }

    public function testAddFilterByTargetEntityWithInvalidActivityEntityClassSpecified()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "Entity\NotRoot" must be the root entity.');

        $targetEntityClass = Target::class;
        $targetEntityId = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from(Activity::class, 'activity')
            ->from(Another::class, 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity(
            $qb,
            $targetEntityClass,
            $targetEntityId,
            'Entity\NotRoot'
        );
    }
}
