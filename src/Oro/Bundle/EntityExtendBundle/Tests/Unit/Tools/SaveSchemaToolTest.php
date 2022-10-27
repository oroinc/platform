<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tools\SaveSchemaTool;
use Psr\Log\LoggerInterface;

class SaveSchemaToolTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var Configuration|\PHPUnit\Framework\MockObject\MockObject */
    private $configuration;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var SaveSchemaTool|\PHPUnit\Framework\MockObject\MockObject */
    private $schemaTool;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->schemaTool = $this->getMockBuilder(SaveSchemaTool::class)
            ->onlyMethods(['getSchemaFromMetadata'])
            ->setConstructorArgs([$this->managerRegistry, $this->logger])
            ->getMock();
    }

    public function testGetUpdateSchemaSqlShouldAffectOnlyKnownDBTableParts()
    {
        [$fromSchema, $toSchema] = $this->prepareSchemas();

        $meta = [new \stdClass()];

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $this->schemaTool->expects($this->once())
            ->method('getSchemaFromMetadata')
            ->with($meta)
            ->willReturn($toSchema);

        $this->assertEquals(
            ['DROP INDEX oro_idx_index_name ON oro_entity_extend_test_relation'],
            $this->schemaTool->getUpdateSchemaSql($meta, true)
        );
    }

    /**
     * @return Schema[]
     */
    private function prepareSchemas(): array
    {
        Type::addType('int', IntegerType::class);

        $fromSchema = new Schema();
        $table = $fromSchema->createTable('oro_entity_extend_test_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('someExternalColumn', 'string');
        $table->addColumn('relation_id', 'int');
        $table->addIndex(['someExternalColumn'], 'some_external_index_name');
        $table->setPrimaryKey(['id']);

        $tableRelation = $fromSchema->createTable('oro_entity_extend_test_relation');
        $tableRelation->addColumn('id', 'integer', ['autoincrement' => true]);
        $tableRelation->addColumn('someExtendColumn', 'string');
        $tableRelation->addIndex(['someExtendColumn'], 'oro_idx_index_name');
        $tableRelation->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint($tableRelation, ['relation_id'], ['id']);

        $toSchema = new Schema();
        $table = $toSchema->createTable('oro_entity_extend_test_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $tableRelation = $toSchema->createTable('oro_entity_extend_test_relation');
        $tableRelation->addColumn('id', 'integer', ['autoincrement' => true]);
        $tableRelation->setPrimaryKey(['id']);

        return [$fromSchema, $toSchema];
    }
}
