<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\TriggerDriver;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\TriggerDriver\PdoPgsql;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PdoPgsqlTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private PdoPgsql $testable;

    private string $testEntityClass = 'testEntity';

    private Connection&MockObject $connection;

    private string $tableName = 'oro_test_entity';

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->testable = new PdoPgsql($this->doctrineHelper);

        $this->testable->setEntityClass($this->testEntityClass);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects($this->any())
            ->method('getTableName')
            ->willReturn($this->tableName);

        $entityManager = $this->createMock(EntityManager::class);

        $entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->testEntityClass)
            ->willReturn($classMetadata);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with($this->testEntityClass)
            ->willReturn($entityManager);
    }

    public function testGetName(): void
    {
        $this->assertEquals(PdoPgsql::DRIVER_POSTGRESQL, $this->testable->getName());
    }

    public function testEnable(): void
    {
        $expectedSql = 'ALTER TABLE oro_test_entity ENABLE TRIGGER ALL';

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql);

        $this->testable->enable();
    }

    public function testDisable(): void
    {
        $expectedSql = 'ALTER TABLE oro_test_entity DISABLE TRIGGER ALL';

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql);

        $this->testable->disable();
    }
}
