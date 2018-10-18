<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\TriggerDriver;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoMysql;

class PdoMysqlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var PdoMysql
     */
    private $testable;

    /**
     * @var string
     */
    private $testEntityClass = 'testEntity';

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName = 'oro_test_entity';

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->testable = new PdoMysql($this->doctrineHelper);

        $this->testable->setEntityClass($this->testEntityClass);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->method('getTableName')
            ->willReturn($this->tableName);

        $entityManager = $this->createMock(EntityManager::class);

        $entityManager->method('getConnection')
            ->willReturn($this->connection);

        $entityManager->method('getClassMetadata')
            ->with($this->testEntityClass)
            ->willReturn($classMetadata);

        $this->doctrineHelper->method('getEntityManagerForClass')
            ->with($this->testEntityClass)
            ->willReturn($entityManager);
    }

    public function testGetName()
    {
        $this->assertEquals(PdoMysql::DRIVER_MYSQL, $this->testable->getName());
    }

    public function testEnable()
    {
        $expectedSql = 'SET FOREIGN_KEY_CHECKS = 1';

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $this->testable->enable();
    }

    public function testDisable()
    {
        $expectedSql = 'SET FOREIGN_KEY_CHECKS = 0';

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $this->testable->disable();
    }
}
