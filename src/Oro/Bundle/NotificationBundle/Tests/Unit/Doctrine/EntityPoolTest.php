<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use PHPUnit\Framework\MockObject\MockObject;

class EntityPoolTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|EntityManagerInterface */
    private $entityManager;

    /** @var EntityPool */
    private $entityPool;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityPool = new EntityPool();
    }

    public function testPersistAndClearWithNoEntities()
    {
        $this->entityManager->expects(static::never())->method(static::anything());

        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testAddPersistAndClear()
    {
        $fooEntity = $this->createMock(\ArrayObject::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(static::exactly(2))
            ->method('persist')
            ->withConsecutive(
                [static::identicalTo($fooEntity)],
                [static::identicalTo($barEntity)]
            );
        $this->entityManager->expects(static::never())->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);

        $this->entityManager->expects(static::never())->method('persist');
        $this->entityManager->expects(static::never())->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testPersistAndFlushWithNoEntities()
    {
        $this->entityManager->expects(static::never())->method(static::anything());

        $this->entityPool->persistAndFlush($this->entityManager);
    }

    public function testAddPersistAndFlush()
    {
        $fooEntity = $this->createMock(\ArrayObject::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(static::exactly(2))
            ->method('persist')
            ->withConsecutive(
                [static::identicalTo($fooEntity)],
                [static::identicalTo($barEntity)]
            );
        $this->entityManager->expects(static::once())->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);

        $this->entityManager->expects(static::never())->method('persist');
        $this->entityManager->expects(static::never())->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);
    }
}
