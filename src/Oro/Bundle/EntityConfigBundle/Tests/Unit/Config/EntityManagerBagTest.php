<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityManagerBagTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerBag $entityManagerBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->entityManagerBag = new EntityManagerBag($this->doctrine);
    }

    public function testGetEntityManagersWithoutAdditionalEntityManagers(): void
    {
        $defaultEm = $this->createMock(EntityManager::class);

        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with(null)
            ->willReturn($defaultEm);

        $result = $this->entityManagerBag->getEntityManagers();
        $this->assertCount(1, $result);
        $this->assertSame($defaultEm, $result[0]);
    }

    public function testGetEntityManagers(): void
    {
        $defaultEm = $this->createMock(EntityManager::class);
        $anotherEm = $this->createMock(EntityManager::class);

        $this->doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->withConsecutive([null], ['another'])
            ->willReturnOnConsecutiveCalls($defaultEm, $anotherEm);

        $this->entityManagerBag->addEntityManager('another');

        $result = $this->entityManagerBag->getEntityManagers();
        $this->assertCount(2, $result);
        $this->assertSame($defaultEm, $result[0]);
        $this->assertSame($anotherEm, $result[1]);
    }
}
