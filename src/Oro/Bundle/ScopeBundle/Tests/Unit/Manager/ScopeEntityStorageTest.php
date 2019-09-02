<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeEntityStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ScopeEntityStorage */
    private $storage;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->storage = new ScopeEntityStorage($this->registry);
    }

    public function testScheduleForInsert()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(['test' => 1], new ClassMetadata(Scope::class));

        $this->storage->scheduleForInsert($scope, $scopeCriteria);
        $this->assertSame($scope, $this->storage->getScheduledForInsertByCriteria($scopeCriteria));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($scope);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);
        $this->storage->persistScheduledForInsert();

        $this->storage->clear();
        $this->assertNull($this->storage->getScheduledForInsertByCriteria($scopeCriteria));
    }

    public function testFlushEmpty()
    {
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->storage->flush();
    }

    public function testFlush()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(['test' => 1], new ClassMetadata(Scope::class));

        $this->storage->scheduleForInsert($scope, $scopeCriteria);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush')
            ->with([$scope]);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);

        $this->storage->flush();
    }

    public function testGetRepository()
    {
        $repository = $this->createMock(ScopeRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Scope::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);

        $this->assertSame($repository, $this->storage->getRepository());
    }

    public function testGetClassMetadata()
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Scope::class)
            ->willReturn($classMetadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);

        $this->assertSame($classMetadata, $this->storage->getClassMetadata());
    }
}
