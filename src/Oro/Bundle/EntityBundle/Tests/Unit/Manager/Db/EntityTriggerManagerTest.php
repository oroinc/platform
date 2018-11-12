<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Manager\Db;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerManager;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoMysql;

class EntityTriggerManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityTriggerManager
     */
    private $testable;

    /**
     * @var string
     */
    private $testEntityClass = 'testEntity';

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->connection = $this->createMock(Connection::class);

        $em = $this->createMock(EntityManager::class);

        $em->method('getConnection')
            ->willReturn($this->connection);

        $this->doctrineHelper->method('getEntityManagerForClass')
            ->with($this->testEntityClass)
            ->willReturn($em);

        $this->testable = new EntityTriggerManager(
            $this->doctrineHelper,
            $this->testEntityClass
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDriverException()
    {
        $this->testable->disable();
    }

    public function testAddingDriver()
    {
        /** @var DatabaseDriverInterface|\PHPUnit\Framework\MockObject\MockObject $driver */
        $driver = $this->createMock(PdoMysql::class);

        $driver->expects($this->once())
            ->method('getName')
            ->willReturn('driverName');

        $this->testable->addDriver($driver);

        $connectionParams = [
            'driver' => 'driverName'
        ];

        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn($connectionParams);

        $driver->expects($this->once())
            ->method('setEntityClass')
            ->with($this->testEntityClass);

        $driver->expects($this->once())
            ->method('disable');

        $this->testable->disable();
    }
}
