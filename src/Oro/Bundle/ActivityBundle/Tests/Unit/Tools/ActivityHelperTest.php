<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target;
use Oro\Bundle\ActivityBundle\Tools\ActivityHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class ActivityHelperTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var ActivityHelper */
    protected $activityHelper;

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

        $this->activityHelper = new ActivityHelper(
            new DoctrineHelper($doctrine),
            new EntityClassResolver($doctrine)
        );
    }

    public function testAddFilterByTargetEntity()
    {
        $targetEntityId = 123;
        $targetEntity   = new Target($targetEntityId);

        $qb = $this->em->getRepository('Test:Activity')->createQueryBuilder('activity')
            ->select('activity');

        $this->activityHelper->addFilterByTargetEntity($qb, $targetEntity);

        $this->assertEquals(
            'SELECT activity'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity activity'
            . ' WHERE activity.id IN('
            . 'SELECT filterActivityEntity.id'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity filterActivityEntity'
            . ' INNER JOIN filterActivityEntity.target_bcaa0d48 filterTargetEntity'
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
        $targetEntityId = 123;
        $targetEntity   = new Target($targetEntityId);

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->activityHelper->addFilterByTargetEntity(
            $qb,
            $targetEntity,
            'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity'
        );

        $this->assertEquals(
            'SELECT activity, another'
            . ' FROM Test:Activity activity, Test:Another another'
            . ' WHERE another.id = activity.id AND activity.id IN('
            . 'SELECT filterActivityEntity.id'
            . ' FROM Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Activity filterActivityEntity'
            . ' INNER JOIN filterActivityEntity.target_bcaa0d48 filterTargetEntity'
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
        $targetEntityId = 123;
        $targetEntity   = new Target($targetEntityId);

        $qb = $this->em->createQueryBuilder();

        $this->activityHelper->addFilterByTargetEntity($qb, $targetEntity);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The $activityEntityClass must be specified if the query has several root entities.
     */
    public function testAddFilterByTargetEntityWithSeveralRootEntitiesButWithoutActivityEntityClassSpecified()
    {
        $targetEntityId = 123;
        $targetEntity   = new Target($targetEntityId);

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->activityHelper->addFilterByTargetEntity($qb, $targetEntity);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Entity\NotRoot" must be the root entity.
     */
    public function testAddFilterByTargetEntityWithInvalidActivityEntityClassSpecified()
    {
        $targetEntityId = 123;
        $targetEntity   = new Target($targetEntityId);

        $qb = $this->em->createQueryBuilder()
            ->select('activity, another')
            ->from('Test:Activity', 'activity')
            ->from('Test:Another', 'another')
            ->where('another.id = activity.id');

        $this->activityHelper->addFilterByTargetEntity(
            $qb,
            $targetEntity,
            'Entity\NotRoot'
        );
    }
}
