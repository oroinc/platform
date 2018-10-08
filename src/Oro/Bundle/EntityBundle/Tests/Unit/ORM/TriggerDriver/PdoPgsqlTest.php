<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\TriggerDriver;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoPgsql;

class PdoPgsqlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var PdoPgsql
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

        $this->testable = new PdoPgsql($this->doctrineHelper);

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
        $this->assertEquals(PdoPgsql::DRIVER_POSTGRESQL, $this->testable->getName());
    }

    public function testEnable()
    {
        $expectedSql = 'ALTER TABLE oro_test_entity ENABLE TRIGGER ALL';

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $this->testable->enable();
    }

    public function testDisable()
    {
        $expectedSql = 'ALTER TABLE oro_test_entity DISABLE TRIGGER ALL';

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $this->testable->disable();
    }
}
