<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\CollectListManager;

class CollectListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $chainProvider;

    /** @var CollectListManager */
    protected $manager;

    public function setUp()
    {
        $this->chainProvider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new CollectListManager($this->chainProvider);
    }

    public function testIsSupportedEntity()
    {
        $correctEntity = new \stdClass();
        $nonCorrectEntity = new \stdClass();

        $this->chainProvider->expects($this->any())
            ->method('isSupportedEntity')
            ->will($this->returnCallback(
                function ($input) use ($correctEntity) {
                    return $input === $correctEntity;
                }
            ));
        $this->assertTrue($this->manager->isSupportedEntity($correctEntity));
        $this->assertFalse($this->manager->isSupportedEntity($nonCorrectEntity));
    }

    public function testProcessDeletedEntities()
    {
        $deleteData = [
            ['class' => 'Acme\\TestBundle\\Entity\\TestEntity', 'id' => 10]
        ];
        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('deleteActivityListsByRelatedActivityData')
            ->with('Acme\\TestBundle\\Entity\\TestEntity', 10);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')->will($this->returnValue($repo));

        $this->manager->processDeletedEntities($deleteData, $em);
    }

    public function testProcessEmptyInsertEntities()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->assertFalse($this->manager->processInsertEntities([], $em));
    }

    public function testProcessInsertEntities()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $testEntity = new \stdClass();
        $resultActivityList = new ActivityList();
        $this->chainProvider->expects($this->once())
            ->method('getActivityListEntitiesByActivityEntity')
            ->with($testEntity)
            ->willReturn($resultActivityList);
        $em->expects($this->once())
            ->method('persist')
            ->with($resultActivityList);
        $this->assertTrue($this->manager->processInsertEntities([$testEntity], $em));
    }

    public function testProcessEmptyUpdateEntities()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->assertFalse($this->manager->processUpdatedEntities([], $em));
    }

    public function testProcessUpdateEntities()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $testEntity = new \stdClass();
        $resultActivityList = new ActivityList();
        $this->chainProvider->expects($this->once())
            ->method('getUpdatedActivityList')
            ->with($testEntity)
            ->willReturn($resultActivityList);
        $em->expects($this->once())
            ->method('persist')
            ->with($resultActivityList);
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $metaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with('Oro\Bundle\ActivityListBundle\Entity\ActivityList')
            ->willReturn($metaData);
        $uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($metaData, $resultActivityList);
        $this->assertTrue($this->manager->processUpdatedEntities([$testEntity], $em));
    }

    public function testProcessFillOwners()
    {
        $activityOwner = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\ActivityOwner')
            ->setMethods(['isOwnerInCollection'])->disableOriginalConstructor()->getMock();
        $activityOwner->expects($this->exactly(2))
            ->method('isOwnerInCollection')
            ->willReturn(false);
        $activity = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\ActivityList')
            ->disableOriginalConstructor()->getMock();
        $activity->expects($this->once())
            ->method('getActivityOwners')
            ->willReturn(new ArrayCollection([$activityOwner]));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $testEntity = new \stdClass();
        $this->chainProvider->expects($this->once())
            ->method('getActivityListByEntity')
            ->willReturn($activity);
        $emailProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->disableOriginalConstructor()->getMock();
        $emailProvider->expects($this->once())
            ->method('getActivityOwners')
            ->willReturn([$activityOwner]);
        $this->chainProvider->expects($this->once())
            ->method('getProviderForOwnerEntity')
            ->willReturn($emailProvider);
        $activity->expects($this->once())
            ->method('removeActivityOwner');
        $activity->expects($this->once())
            ->method('addActivityOwner');

        $this->manager->processFillOwners([$testEntity], $em);
    }

    public function testIsSupportedOwnerEntity()
    {
        $testEntity = new \stdClass();

        $this->chainProvider->expects($this->once())
            ->method('isSupportedOwnerEntity')
            ->with($testEntity)
            ->willReturn(true);

        $this->assertTrue($this->manager->isSupportedOwnerEntity($testEntity));
    }
}
