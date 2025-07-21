<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityStateCheckerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityStateChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $propertyAccessor = new PropertyAccessor();

        $this->checker = new EntityStateChecker($this->doctrineHelper, $propertyAccessor);
    }

    public function testIsNewEntity(): void
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(true);

        self::assertTrue($this->checker->isNewEntity($entity));
    }

    public function testIsChangedEntityWhenNoFieldNamesToTrack(): void
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());

        $this->expectExceptionObject(
            new \InvalidArgumentException('Argument $fieldNamesToCheck was not expected to be empty')
        );

        $this->checker->isChangedEntity($entity, []);
    }

    public function testIsChangedEntityWhenEmptyOriginalData(): void
    {
        $entity = new \stdClass();

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($entity)
            ->willReturn([]);

        self::assertTrue($this->checker->isChangedEntity($entity, ['sampleField']));
    }

    public function testIsChangedEntityWhenChanged(): void
    {
        $entity = new \stdClass();
        $entity->sampleField1 = 'updated_value1';
        $entity->sampleField2 = 'sample_value2';

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($entity)
            ->willReturn(['sampleField1' => 'sample_value1', 'sampleField2' => 'sample_value2']);

        self::assertTrue($this->checker->isChangedEntity($entity, ['sampleField1', 'sampleField2']));
    }

    public function testIsChangedEntityWhenNotChanged(): void
    {
        $entity = new \stdClass();
        $entity->sampleField1 = 'sample_value1';
        $entity->sampleField2 = 'sample_value2';

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($entity)
            ->willReturn(['sampleField1' => 'sample_value1', 'sampleField2' => 'sample_value2']);

        self::assertFalse($this->checker->isChangedEntity($entity, ['sampleField1', 'sampleField2']));
    }
}
