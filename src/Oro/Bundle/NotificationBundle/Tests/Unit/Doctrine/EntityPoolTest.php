<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Doctrine;

use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;

class EntityPoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var EntityPool
     */
    protected $entityPool;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityPool = new EntityPool();
    }

    public function testAddPersistEntity()
    {
        $entity = $this->createTestEntity();
        $this->entityPool->addPersistEntity($entity);

        $this->assertAttributeEquals(array($entity), 'persistEntities', $this->entityPool);
    }

    public function testAddPersistAndClearWithNoEntities()
    {
        $this->entityManager->expects($this->never())->method($this->anything());
        $this->entityPool->persistAndClear($this->entityManager);
    }

    public function testAddPersistAndClear()
    {
        $this->entityPool->addPersistEntity($fooEntity = $this->createTestEntity());
        $this->entityPool->addPersistEntity($barEntity = $this->createTestEntity());

        $this->entityManager->expects($this->at(0))
            ->method('persist')
            ->with($fooEntity);

        $this->entityManager->expects($this->at(1))
            ->method('persist')
            ->with($barEntity);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->entityPool->persistAndClear($this->entityManager);

        $this->assertAttributeEquals(array(), 'persistEntities', $this->entityPool);
    }

    public function testAddPersistAndFlushWithNoEntities()
    {
        $this->entityManager->expects($this->never())->method($this->anything());
        $this->entityPool->persistAndFlush($this->entityManager);
    }

    public function testAddPersistAndFlush()
    {
        $this->entityPool->addPersistEntity($fooEntity = $this->createTestEntity());
        $this->entityPool->addPersistEntity($barEntity = $this->createTestEntity());

        $this->entityManager->expects($this->at(0))
            ->method('persist')
            ->with($fooEntity);

        $this->entityManager->expects($this->at(1))
            ->method('persist')
            ->with($barEntity);

        $this->entityManager->expects($this->at(2))
            ->method('flush');

        $this->entityPool->persistAndFlush($this->entityManager);

        $this->assertAttributeEquals(array(), 'persistEntities', $this->entityPool);
    }

    protected function createEntityManager()
    {
        return $this->createMock(\ArrayObject::class);
    }

    protected function createTestEntity()
    {
        return $this->createMock(\ArrayObject::class);
    }
}
