<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class ActivityManagerTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var ActivityManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity'
            ]
        );

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));
        $doctrine->expects($this->any())
            ->method('getAliasNamespace')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test', 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity']
                    ]
                )
            );

        $this->activityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->associationManager =
            $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->manager = new ActivityManager(
            new DoctrineHelper($doctrine),
            new EntityClassResolver($doctrine),
            $this->activityConfigProvider,
            $this->groupingConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->associationManager
        );

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue($targetEntityActivityConfig));

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
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue($targetEntityActivityConfig));

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
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->manager->hasActivityAssociations($targetEntityClass)
        );
    }

    public function testHasActivityAssociation()
    {
        $activityEntityClass = 'Test\Activity';
        $targetEntityClass   = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', ['Test\OtherActivity', $activityEntityClass]);

        $this->activityConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue($targetEntityActivityConfig));

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
        $targetEntityClass   = 'Test\Entity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue($targetEntityActivityConfig));

        $this->assertFalse(
            $this->manager->hasActivityAssociation($targetEntityClass, $activityEntityClass)
        );
    }

    public function testHasActivityAssociationForNonConfigurableEntity()
    {
        $activityEntityClass = 'Test\Activity';
        $targetEntityClass   = 'Test\Entity';

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetEntityClass)
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->manager->hasActivityAssociation($targetEntityClass, $activityEntityClass)
        );
    }

    public function testAddActivityTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(false));
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue($activityEntity));
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::ADD_ACTIVITY);

        $this->assertTrue(
            $this->manager->addActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testAddActivityTargetForAlreadyAddedTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testAddActivityTargetForNotSupportedTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(false));
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
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

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
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(false));
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue($activityEntity));

        $this->assertTrue(
            $this->manager->addActivityTargets($activityEntity, [$targetEntity])
        );
    }

    public function testAddActivityTargetsForAlreadyAddedTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->addActivityTargets($activityEntity, [$targetEntity])
        );
    }

    public function testAddActivityTargetsForNotSupportedTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(false));
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
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue($activityEntity));
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::REMOVE_ACTIVITY);

        $this->assertTrue(
            $this->manager->removeActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testRemoveActivityTargetForNotExistingTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(true));
        $activityEntity->expects($this->once())
            ->method('hasActivityTarget')
            ->with($this->identicalTo($targetEntity))
            ->will($this->returnValue(false));
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($targetEntity));

        $this->assertFalse(
            $this->manager->removeActivityTarget($activityEntity, $targetEntity)
        );
    }

    public function testRemoveActivityTargetForNotSupportedTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');
        $targetEntity   = new Target();

        $activityEntity->expects($this->once())
            ->method('supportActivityTarget')
            ->with(get_class($targetEntity))
            ->will($this->returnValue(false));
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
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

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
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [get_class($oldTargetEntity), true],
                        [get_class($newTargetEntity), true],
                    ]
                )
            );
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [$oldTargetEntity, true],
                        [$newTargetEntity, false],
                    ]
                )
            );
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($oldTargetEntity))
            ->will($this->returnValue($activityEntity));
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($newTargetEntity))
            ->will($this->returnValue($activityEntity));

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNoAssociationWithOldTarget()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [get_class($oldTargetEntity), true],
                        [get_class($newTargetEntity), true],
                    ]
                )
            );
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [$oldTargetEntity, false],
                        [$newTargetEntity, false],
                    ]
                )
            );
        $activityEntity->expects($this->never())
            ->method('removeActivityTarget');
        $activityEntity->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($newTargetEntity))
            ->will($this->returnValue($activityEntity));

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNewTargetAlreadyExist()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [get_class($oldTargetEntity), true],
                        [get_class($newTargetEntity), true],
                    ]
                )
            );
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [$oldTargetEntity, true],
                        [$newTargetEntity, true],
                    ]
                )
            );
        $activityEntity->expects($this->once())
            ->method('removeActivityTarget')
            ->with($this->identicalTo($oldTargetEntity))
            ->will($this->returnValue($activityEntity));
        $activityEntity->expects($this->never())
            ->method('addActivityTarget');

        $this->assertTrue(
            $this->manager->replaceActivityTarget($activityEntity, $oldTargetEntity, $newTargetEntity)
        );
    }

    public function testReplaceActivityTargetNoChanges()
    {
        $activityEntity = $this->getMock('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

        $oldTargetEntity = new Target(1);
        $newTargetEntity = new Target(2);

        $activityEntity->expects($this->exactly(2))
            ->method('supportActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [get_class($oldTargetEntity), true],
                        [get_class($newTargetEntity), true],
                    ]
                )
            );
        $activityEntity->expects($this->exactly(2))
            ->method('hasActivityTarget')
            ->will(
                $this->returnValueMap(
                    [
                        [$oldTargetEntity, false],
                        [$newTargetEntity, true],
                    ]
                )
            );
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
        $activity1Class    = 'Test\Activity1';
        $activity2Class    = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

        $activityAssociationName   = ExtendHelper::buildAssociationName(
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
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, $activityAssociationName, true],
                        [$activity2Class, $activityAssociationName, true],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, $activityAssociationName, $activityAssociation1Config],
                        [$activity2Class, $activityAssociationName, $activityAssociation2Config],
                    ]
                )
            );
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, null, $activity1EntityConfig],
                        [$activity2Class, null, $activity2EntityConfig],
                    ]
                )
            );
        $this->activityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$targetEntityClass, null, $targetEntityActivityConfig],
                        [$activity1Class, null, $activity1ActivityConfig],
                        [$activity2Class, null, $activity2ActivityConfig],
                    ]
                )
            );

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
        $activityClass     = 'Test\Activity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activityClass]);

        $activityAssociationName   = ExtendHelper::buildAssociationName(
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
            ->will($this->returnValue($targetEntityActivityConfig));
        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($activityClass, $activityAssociationName)
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($activityClass, $activityAssociationName)
            ->will($this->returnValue($activityAssociationConfig));
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
        $activity1Class    = 'Test\Activity1';
        $activity2Class    = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

        $activityAssociationName   = ExtendHelper::buildAssociationName(
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
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, $activityAssociationName, true],
                        [$activity2Class, $activityAssociationName, true],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, $activityAssociationName, $activityAssociation1Config],
                        [$activity2Class, $activityAssociationName, $activityAssociation2Config],
                    ]
                )
            );
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$activity1Class, null, $activity1EntityConfig],
                        [$activity2Class, null, $activity2EntityConfig],
                    ]
                )
            );
        $this->activityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$targetEntityClass, null, $targetEntityActivityConfig],
                        [$activity1Class, null, $activity1ActivityConfig],
                        [$activity2Class, null, $activity2ActivityConfig],
                    ]
                )
            );

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
        $activityClass     = 'Test\Activity';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activityClass]);

        $activityAssociationName   = ExtendHelper::buildAssociationName(
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
            ->will($this->returnValue($targetEntityActivityConfig));
        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($activityClass, $activityAssociationName)
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($activityClass, $activityAssociationName)
            ->will($this->returnValue($activityAssociationConfig));
        $this->entityConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(
            [],
            $this->manager->getActivityActions($targetEntityClass)
        );
    }

    public function testAddFilterByTargetEntity()
    {
        $targetEntityClass = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';
        $targetEntityId    = 123;

        $qb = $this->em->getRepository('Test:Activity')->createQueryBuilder('activity')
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
        $targetEntityClass = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';
        $targetEntityId    = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity(
            $qb,
            $targetEntityClass,
            $targetEntityId,
            'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity'
        );

        $this->assertEquals(
            'SELECT activity, another'
            . ' FROM Test:Activity activity, Test:Another another'
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The query must have at least one root entity.
     */
    public function testAddFilterByTargetEntityWithEmptyQuery()
    {
        $targetEntityClass = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';
        $targetEntityId    = 123;

        $qb = $this->em->createQueryBuilder();

        $this->manager->addFilterByTargetEntity($qb, $targetEntityClass, $targetEntityId);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The $activityEntityClass must be specified if the query has several root entities.
     */
    public function testAddFilterByTargetEntityWithSeveralRootEntitiesButWithoutActivityEntityClassSpecified()
    {
        $targetEntityClass = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';
        $targetEntityId    = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity($qb, $targetEntityClass, $targetEntityId);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Entity\NotRoot" must be the root entity.
     */
    public function testAddFilterByTargetEntityWithInvalidActivityEntityClassSpecified()
    {
        $targetEntityClass = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';
        $targetEntityId    = 123;

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->manager->addFilterByTargetEntity(
            $qb,
            $targetEntityClass,
            $targetEntityId,
            'Entity\NotRoot'
        );
    }
}
