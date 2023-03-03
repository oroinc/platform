<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\DoctrineUtils\Tests\Unit\Stub\DummyEntity;

class FieldUpdatesCheckerTest extends \PHPUnit\Framework\TestCase
{
    private const RELATION_FIELD = 'relationEntity';
    private const RELATION_COLLECTION_FIELD = 'relationEntityCollection';

    public function testIsFieldChangedWhenUnitOfWorkHasUpdates()
    {
        $updatedEntityOne = (new DummyEntity())->setId(1);

        $updatedEntityTwo = new DummyEntity();
        $updatedEntityTwo->setId(2)->setRelationEntity($updatedEntityOne);

        $fieldUpdatesChecker = new FieldUpdatesChecker(
            $this->getManagerRegistry([$updatedEntityOne, $updatedEntityTwo]),
            PropertyAccess::createPropertyAccessor()
        );

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $updatedEntityTwo,
            self::RELATION_FIELD
        ));
    }

    public function testIsFieldChangedWhenUnitOfWorkHasUpdatesWithCollection()
    {
        $changedEntityOne = (new DummyEntity())->setId(1);
        $changedEntityTwo = (new DummyEntity())->setId(2);

        $changedEntityThree = new DummyEntity();
        $changedEntityThree
            ->setId(3)
            ->setRelationEntityCollection(new ArrayCollection([$changedEntityOne, $changedEntityTwo]));

        $fieldUpdatesChecker = new FieldUpdatesChecker(
            $this->getManagerRegistry([$changedEntityOne, $changedEntityThree]),
            PropertyAccess::createPropertyAccessor()
        );

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $changedEntityThree,
            self::RELATION_COLLECTION_FIELD
        ));
    }

    public function testIsFieldChangedWhenUnitOfWorkHasNoUpdates()
    {
        $changedEntityOne = (new DummyEntity())->setId(1);

        $changedEntityTwo = new DummyEntity();
        $changedEntityTwo->setId(2)->setRelationEntity($changedEntityOne);

        // unit of work will have only $changedEntityTwo in 'updates'
        $fieldUpdatesChecker = new FieldUpdatesChecker(
            $this->getManagerRegistry([$changedEntityTwo]),
            PropertyAccess::createPropertyAccessor()
        );

        $this->assertFalse($fieldUpdatesChecker->isRelationFieldChanged(
            $changedEntityTwo,
            self::RELATION_FIELD
        ));
    }

    public function testIsFieldChangedWhenUnitOfWorkHasUpdatesDeletesAndInsertions()
    {
        $updatedEntityOne = (new DummyEntity())->setId(1);

        $updatedEntityTwo = new DummyEntity();
        $updatedEntityTwo->setId(2)->setRelationEntity($updatedEntityOne);

        $deletedEntityOne = (new DummyEntity())->setId(1);

        $deletedEntityTwo = new DummyEntity();
        $deletedEntityTwo->setId(2)->setRelationEntity($deletedEntityOne);

        $insertedEntityOne = (new DummyEntity())->setId(1);

        $insertedEntityTwo = new DummyEntity();
        $insertedEntityTwo->setId(2)->setRelationEntity($insertedEntityTwo);

        $manager = $this->getManagerRegistry(
            [$updatedEntityOne, $updatedEntityTwo],
            [$deletedEntityOne, $deletedEntityTwo],
            [$insertedEntityOne, $insertedEntityTwo]
        );

        $fieldUpdatesChecker = new FieldUpdatesChecker($manager, PropertyAccess::createPropertyAccessor());

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $updatedEntityTwo,
            self::RELATION_FIELD
        ));

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $deletedEntityTwo,
            self::RELATION_FIELD
        ));

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $insertedEntityTwo,
            self::RELATION_FIELD
        ));
    }

    /**
     * @param array $insertedEntities
     * @param array $updatedEntities
     * @param array $deletedEntities
     *
     * @return ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getManagerRegistry(
        array $updatedEntities = [],
        array $insertedEntities = [],
        array $deletedEntities = []
    ) {
        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updatedEntities);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        return $managerRegistry;
    }
}
