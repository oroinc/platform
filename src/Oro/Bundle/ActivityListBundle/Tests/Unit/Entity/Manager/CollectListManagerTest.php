<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Entity\Manager\CollectListManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CollectListManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityListChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $chainProvider;

    /** @var CollectListManager */
    private $manager;

    protected function setUp(): void
    {
        $this->chainProvider = $this->createMock(ActivityListChainProvider::class);

        $this->manager = new CollectListManager($this->chainProvider);
    }

    public function testIsSupportedEntityForActivityEntity(): void
    {
        $entity = new \stdClass();

        $this->chainProvider->expects(self::once())
            ->method('isSupportedEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(true);

        self::assertTrue($this->manager->isSupportedEntity($entity));
    }

    public function testIsSupportedEntityForNotActivityEntity(): void
    {
        $entity = new \stdClass();

        $this->chainProvider->expects(self::once())
            ->method('isSupportedEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(false);

        self::assertFalse($this->manager->isSupportedEntity($entity));
    }

    public function testIsSupportedOwnerEntityForActivityOwnerEntity(): void
    {
        $entity = new \stdClass();

        $this->chainProvider->expects(self::once())
            ->method('isSupportedOwnerEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(true);

        self::assertTrue($this->manager->isSupportedOwnerEntity($entity));
    }

    public function testIsSupportedOwnerEntityForNotActivityOwnerEntity(): void
    {
        $entity = new \stdClass();

        $this->chainProvider->expects(self::once())
            ->method('isSupportedOwnerEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(false);

        self::assertFalse($this->manager->isSupportedOwnerEntity($entity));
    }

    public function testProcessDeletedEntitiesWhenNoEntities(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method(self::anything());

        $this->manager->processDeletedEntities([], $em);
    }

    public function testProcessDeletedEntities(): void
    {
        $deletedEntities = [
            ['class' => 'Acme\TestBundle\Entity\TestEntity', 'id' => 10]
        ];

        $repo = $this->createMock(ActivityListRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('deleteActivityListsByRelatedActivityData')
            ->with('Acme\TestBundle\Entity\TestEntity', 10);

        $this->manager->processDeletedEntities($deletedEntities, $em);
    }

    public function testProcessUpdateEntitiesWhenNoEntities(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->manager->processUpdatedEntities([], $em));
    }

    public function testProcessUpdateEntities(): void
    {
        $testEntity = new \stdClass();
        $activityList = new ActivityList();

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(ActivityList::class)
            ->willReturn($metadata);

        $this->chainProvider->expects(self::once())
            ->method('getUpdatedActivityList')
            ->with($testEntity)
            ->willReturn($activityList);
        $em->expects(self::once())
            ->method('persist')
            ->with($activityList);
        $uow->expects(self::once())
            ->method('computeChangeSet')
            ->with($metadata, $activityList);

        self::assertTrue($this->manager->processUpdatedEntities([$testEntity], $em));
    }

    public function testProcessInsertEntitiesWhenNoEntities(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->manager->processInsertEntities([], $em));
    }

    public function testProcessInsertEntities(): void
    {
        $testEntity = new \stdClass();

        $organization = new Organization();
        $user = new User();
        $user->setId(2);

        $newActivityOwner = new ActivityOwner();
        $newActivityOwner->setOrganization($organization);
        $newActivityOwner->setUser($user);

        $activityList = new ActivityList();

        $this->chainProvider->expects(self::once())
            ->method('getActivityListEntitiesByActivityEntity')
            ->with($testEntity)
            ->willReturn($activityList);

        $activityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->chainProvider->expects(self::once())
            ->method('getProviderForEntity')
            ->willReturn($activityListProvider);
        $activityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->willReturn([$newActivityOwner]);
        $activityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with($activityList);

        self::assertTrue($this->manager->processInsertEntities([$testEntity], $em));

        self::assertTrue($activityList->getActivityOwners()->contains($newActivityOwner));
    }

    public function testProcessInsertEntitiesWhenActivityListIsNotApplicable(): void
    {
        $testEntity = new \stdClass();

        $organization = new Organization();
        $user = new User();
        $user->setId(2);

        $newActivityOwner = new ActivityOwner();
        $newActivityOwner->setOrganization($organization);
        $newActivityOwner->setUser($user);

        $activityList = new ActivityList();

        $this->chainProvider->expects(self::once())
            ->method('getActivityListEntitiesByActivityEntity')
            ->with($testEntity)
            ->willReturn($activityList);

        $activityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->chainProvider->expects(self::once())
            ->method('getProviderForEntity')
            ->willReturn($activityListProvider);
        $activityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->willReturn([$newActivityOwner]);
        $activityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('persist');

        self::assertTrue($this->manager->processInsertEntities([$testEntity], $em));

        self::assertTrue($activityList->getActivityOwners()->contains($newActivityOwner));
    }

    public function testProcessFillOwnersWhenNoEntities(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->manager->processFillOwners([], $em));
    }

    public function testProcessFillOwners(): void
    {
        $activityList = new ActivityList();

        $user1 = new User();
        $user1->setId(1);
        $user2 = new User();
        $user2->setId(2);

        $existingOwner = new ActivityOwner();
        $existingOwner->setActivity($activityList);
        $existingOwner->setUser($user1);
        $activityList->addActivityOwner($existingOwner);

        $newOwner = new ActivityOwner();
        $newOwner->setActivity($activityList);
        $newOwner->setUser($user2);

        $this->chainProvider->expects(self::once())
            ->method('getActivityListByEntity')
            ->willReturn($activityList);

        $activityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->chainProvider->expects(self::once())
            ->method('getProviderForOwnerEntity')
            ->willReturn($activityListProvider);
        $activityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->willReturn([$newOwner]);
        $activityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('remove')
            ->with($existingOwner);

        self::assertTrue($this->manager->processFillOwners([new \stdClass()], $em));

        self::assertEquals(1, $activityList->getActivityOwners()->count());
        self::assertSame($newOwner, $activityList->getActivityOwners()->first());
    }

    public function testProcessFillOwnersWhenActivityListIsNotApplicable(): void
    {
        $activityList = new ActivityList();

        $user1 = new User();
        $user1->setId(1);
        $user2 = new User();
        $user2->setId(2);

        $existingOwner = new ActivityOwner();
        $existingOwner->setActivity($activityList);
        $existingOwner->setUser($user1);
        $activityList->addActivityOwner($existingOwner);

        $newOwner = new ActivityOwner();
        $newOwner->setActivity($activityList);
        $newOwner->setUser($user2);

        $this->chainProvider->expects(self::once())
            ->method('getActivityListByEntity')
            ->willReturn($activityList);

        $activityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->chainProvider->expects(self::once())
            ->method('getProviderForOwnerEntity')
            ->willReturn($activityListProvider);
        $activityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->willReturn([$newOwner]);
        $activityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [self::identicalTo($existingOwner)],
                [self::identicalTo($activityList)]
            );

        self::assertTrue($this->manager->processFillOwners([new \stdClass()], $em));

        self::assertEquals(1, $activityList->getActivityOwners()->count());
        self::assertSame($newOwner, $activityList->getActivityOwners()->first());
    }
}
