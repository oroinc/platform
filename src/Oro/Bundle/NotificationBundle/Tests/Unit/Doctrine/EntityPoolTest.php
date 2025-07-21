<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityPoolTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityPool $entityPool;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityPool = new EntityPool();
    }

    public function testPersistAndClearWithNoEntities(): void
    {
        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testAddPersistAndClear(): void
    {
        $fooEntity = $this->createMock(\ArrayObject::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive(
                [self::identicalTo($fooEntity)],
                [self::identicalTo($barEntity)]
            );
        $this->entityManager->expects(self::never())
            ->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);

        $this->entityManager->expects(self::never())
            ->method('persist');
        $this->entityManager->expects(self::never())
            ->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testPersistAndFlushWithNoEntities(): void
    {
        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $this->entityPool->persistAndFlush($this->entityManager);
    }

    public function testAddPersistAndFlush(): void
    {
        $fooEntity = $this->createMock(\ArrayObject::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive(
                [self::identicalTo($fooEntity)],
                [self::identicalTo($barEntity)]
            );
        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);

        $this->entityManager->expects(self::never())
            ->method('persist');
        $this->entityManager->expects(self::never())
            ->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);
    }
}
