<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\DoctrineUtils\Tests\Unit\Stub\DummyEntity;
use Oro\Component\PropertyAccess\PropertyAccessor;

class FieldUpdatesCheckerTest extends \PHPUnit\Framework\TestCase
{
    const RELATION_FIELD = 'relationEntity';
    const RELATION_COLLECTION_FIELD = 'relationEntityCollection';

    public function testIsFieldChangedWhenUnitOfWorkHasUpdates()
    {
        $updatedEntityOne = (new DummyEntity())->setId(1);

        $updatedEntityTwo = new DummyEntity();
        $updatedEntityTwo->setId(2)->setRelationEntity($updatedEntityOne);

        $fieldUpdatesChecker = new FieldUpdatesChecker(
            $this->getManagerRegistry([$updatedEntityOne, $updatedEntityTwo]),
            new PropertyAccessor()
        );

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $updatedEntityTwo,
            static::RELATION_FIELD
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
            new PropertyAccessor()
        );

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $changedEntityThree,
            static::RELATION_COLLECTION_FIELD
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
            new PropertyAccessor()
        );

        $this->assertFalse($fieldUpdatesChecker->isRelationFieldChanged(
            $changedEntityTwo,
            static::RELATION_FIELD
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

        $fieldUpdatesChecker = new FieldUpdatesChecker($manager, new PropertyAccessor());

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $updatedEntityTwo,
            static::RELATION_FIELD
        ));

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $deletedEntityTwo,
            static::RELATION_FIELD
        ));

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $insertedEntityTwo,
            static::RELATION_FIELD
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
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uow
            ->method('getScheduledEntityUpdates')
            ->willReturn($updatedEntities);

        $uow
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);

        $uow
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $managerRegistry = $this
            ->getMockBuilder(ManagerRegistry::class)
            ->getMock();

        $managerRegistry
            ->method('getManager')
            ->willReturn($entityManager);

        return $managerRegistry;
    }
}
