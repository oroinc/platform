<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;

class EntityManagerBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityManagerBag */
    private $entityManagerBag;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->entityManagerBag = new EntityManagerBag($this->doctrine);
    }

    public function testGetEntityManagersWithoutAdditionalEntityManagers()
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

    public function testGetEntityManagers()
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
