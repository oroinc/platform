<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Model\AdditionalEntityChangesToAuditStorage;

class AdditionalEntityChangesToAuditStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testWhenNoEntityManagerInStorage()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $storage = new AdditionalEntityChangesToAuditStorage();

        $this->assertFalse($storage->hasEntityUpdates($entityManager));
        $this->assertEquals(new \SplObjectStorage(), $storage->getEntityUpdates($entityManager));
    }

    public function testWhenAddNewEntityManagerAndEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $storage = new AdditionalEntityChangesToAuditStorage();
        $storage->addEntityUpdate($entityManager, $entity, ['some changes']);

        $this->assertTrue($storage->hasEntityUpdates($entityManager));
        $expectedUpdates = new \SplObjectStorage();
        $expectedUpdates->attach($entity, ['some changes']);
        $this->assertEquals($expectedUpdates, $storage->getEntityUpdates($entityManager));
    }

    public function testWhenAddNewEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $newEntity = new \stdClass();
        $storage = new AdditionalEntityChangesToAuditStorage();
        $storage->addEntityUpdate($entityManager, $entity, ['some changes']);
        $storage->addEntityUpdate($entityManager, $newEntity, ['some new changes']);

        $this->assertTrue($storage->hasEntityUpdates($entityManager));
        $expectedUpdates = new \SplObjectStorage();
        $expectedUpdates->attach($entity, ['some changes']);
        $expectedUpdates->attach($newEntity, ['some new changes']);
        $this->assertEquals($expectedUpdates, $storage->getEntityUpdates($entityManager));
    }

    public function testWhenAddNewChangesToOldEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $storage = new AdditionalEntityChangesToAuditStorage();
        $storage->addEntityUpdate($entityManager, $entity, ['some changes']);
        $storage->addEntityUpdate($entityManager, $entity, []);

        $this->assertTrue($storage->hasEntityUpdates($entityManager));
        $expectedUpdates = new \SplObjectStorage();
        $expectedUpdates->attach($entity, ['some changes', 'some new changes']);
        $this->assertEquals($expectedUpdates, $storage->getEntityUpdates($entityManager));
    }
}
