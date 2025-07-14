<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Manager\Db;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoMysql;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityTriggerManagerTest extends TestCase
{
    private EntityTriggerManager $testable;

    private string $testEntityClass = 'testEntity';

    private DoctrineHelper&MockObject $doctrineHelper;
    private Connection&MockObject $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->connection = $this->createMock(Connection::class);

        $em = $this->createMock(EntityManager::class);

        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with($this->testEntityClass)
            ->willReturn($em);

        $this->testable = new EntityTriggerManager(
            $this->doctrineHelper,
            $this->testEntityClass
        );
    }

    public function testDriverException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(['driver' => 'driverName']);

        $this->testable->disable();
    }

    public function testAddingDriver(): void
    {
        $driver = $this->createMock(PdoMysql::class);
        $driver->expects($this->once())
            ->method('getName')
            ->willReturn('driverName');

        $this->testable->addDriver($driver);

        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(['driver' => 'driverName']);

        $driver->expects($this->once())
            ->method('setEntityClass')
            ->with($this->testEntityClass);

        $driver->expects($this->once())
            ->method('disable');

        $this->testable->disable();
    }
}
