<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\DoctrineUtils\Tests\Unit\Stub\DummyEntity;
use Oro\Component\PropertyAccess\PropertyAccessor;

class FieldUpdatesCheckerTest extends \PHPUnit_Framework_TestCase
{
    const RELATION_FIELD = 'relationEntity';
    const RELATION_COLLECTION_FIELD = 'relationEntityCollection';

    public function testIsFieldChangedWhenUnitOfWorkHasUpdates()
    {
        $changedEntityOne = (new DummyEntity())->setId(1);

        $changedEntityTwo = new DummyEntity();
        $changedEntityTwo->setId(2)->setRelationEntity($changedEntityOne);

        $fieldUpdatesChecker = new FieldUpdatesChecker(
            $this->getManagerRegistry([$changedEntityOne, $changedEntityTwo]),
            new PropertyAccessor()
        );

        $this->assertTrue($fieldUpdatesChecker->isRelationFieldChanged(
            $changedEntityTwo,
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

    /**
     * @param array $changedEntities
     *
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getManagerRegistry(array $changedEntities = [])
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uow
            ->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($changedEntities);

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $managerRegistry = $this
            ->getMockBuilder(ManagerRegistry::class)
            ->getMock();

        $managerRegistry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        return $managerRegistry;
    }
}
