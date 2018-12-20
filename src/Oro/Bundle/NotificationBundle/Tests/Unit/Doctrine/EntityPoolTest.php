<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;

class EntityPoolTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $entityManager;

    /** @var EntityPool */
    private $entityPool;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityPool = new EntityPool();
    }

    public function testAddPersistEntity()
    {
        $entity = $this->createTestEntity();
        $this->entityPool->addPersistEntity($entity);

        self::assertAttributeSame([$entity], 'persistEntities', $this->entityPool);
    }

    public function testAddPersistAndClearWithNoEntities()
    {
        $this->entityManager->expects(self::never())
            ->method(self::anything());
        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testAddPersistAndClear()
    {
        $fooEntity = $this->createTestEntity();
        $barEntity = $this->createTestEntity();
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(self::at(0))
            ->method('persist')
            ->with(self::identicalTo($fooEntity));
        $this->entityManager->expects(self::at(1))
            ->method('persist')
            ->with(self::identicalTo($barEntity));
        $this->entityManager->expects(self::never())
            ->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);

        self::assertAttributeSame([], 'persistEntities', $this->entityPool);
    }

    public function testAddPersistAndFlushWithNoEntities()
    {
        $this->entityManager->expects(self::never())
            ->method(self::anything());
        $this->entityPool->persistAndFlush($this->entityManager);
    }

    public function testAddPersistAndFlush()
    {
        $fooEntity = $this->createTestEntity();
        $barEntity = $this->createTestEntity();
        $this->entityPool->addPersistEntity($fooEntity);
        $this->entityPool->addPersistEntity($barEntity);

        $this->entityManager->expects(self::at(0))
            ->method('persist')
            ->with(self::identicalTo($fooEntity));
        $this->entityManager->expects(self::at(1))
            ->method('persist')
            ->with(self::identicalTo($barEntity));
        $this->entityManager->expects(self::at(2))
            ->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);

        self::assertAttributeSame([], 'persistEntities', $this->entityPool);
    }

    private function createTestEntity()
    {
        return $this->createMock(\ArrayObject::class);
    }
}
