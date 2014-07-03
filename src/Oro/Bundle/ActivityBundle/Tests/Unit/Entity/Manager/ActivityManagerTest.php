<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class ActivityManagerTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var ActivityManager */
    protected $manager;

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
        $this->entityConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ActivityManager(
            new DoctrineHelper($doctrine),
            new EntityClassResolver($doctrine),
            $this->activityConfigProvider,
            $this->entityConfigProvider
        );
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

    public function testGetActivityAssociations()
    {
        $targetEntityClass = 'Test\Entity';
        $activity1Class    = 'Test\Activity1';
        $activity2Class    = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

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

    public function testGetActivityActions()
    {
        $targetEntityClass = 'Test\Entity';
        $activity1Class    = 'Test\Activity1';
        $activity2Class    = 'Test\Activity2';

        $targetEntityActivityConfig = new Config(new EntityConfigId('activity', $targetEntityClass));
        $targetEntityActivityConfig->set('activities', [$activity1Class, $activity2Class]);

        $activity1EntityConfig = new Config(new EntityConfigId('entity', $activity1Class));
        $activity1EntityConfig->set('plural_label', 'lbl.activity1');
        $activity1ActivityConfig = new Config(new EntityConfigId('activity', $activity1Class));
        $activity1ActivityConfig->set('action_widget', 'widget1');
        $activity1ActivityConfig->set('action_group', 'group1');

        $activity2EntityConfig = new Config(new EntityConfigId('entity', $activity2Class));
        $activity2EntityConfig->set('plural_label', 'lbl.activity2');
        $activity2ActivityConfig = new Config(new EntityConfigId('activity', $activity2Class));
        $activity2ActivityConfig->set('action_widget', 'widget2');
        $activity2ActivityConfig->set('priority', 100);

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
                    'widget'          => 'widget1',
                    'group'           => 'group1',
                ],
                [
                    'className'       => 'Test\Activity2',
                    'associationName' => 'entity_1f801d4a',
                    'widget'          => 'widget2',
                    'priority'        => 100,
                ],
            ],
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
