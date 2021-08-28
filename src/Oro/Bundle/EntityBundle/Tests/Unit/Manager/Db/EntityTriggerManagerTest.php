<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Manager\Db;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoMysql;

class EntityTriggerManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityTriggerManager */
    private $testable;

    /** @var string */
    private $testEntityClass = 'testEntity';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

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

    public function testDriverException()
    {
        $this->expectException(\RuntimeException::class);
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(['driver' => 'driverName']);

        $this->testable->disable();
    }

    public function testAddingDriver()
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
