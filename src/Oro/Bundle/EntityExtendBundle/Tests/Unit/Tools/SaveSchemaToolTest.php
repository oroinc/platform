<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityExtendBundle\Tools\SaveSchemaTool;

class SaveSchemaToolTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var Configuration|\PHPUnit\Framework\MockObject\MockObject */
    protected $configuration;

    /** @var SaveSchemaTool|\PHPUnit\Framework\MockObject\MockObject */
    protected $schemaTool;

    protected function setUp()
    {
        $this->connection    = $this->createMock('Doctrine\DBAL\Connection');
        $this->configuration = $this->createMock('Doctrine\ORM\Configuration');
        $this->em            = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $this->em->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->em->expects($this->any())->method('getConfiguration')->willReturn($this->configuration);

        $this->connection->expects($this->any())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());

        $this->schemaTool = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\SaveSchemaTool')
            ->setMethods(['getSchemaFromMetadata'])
            ->setConstructorArgs([$this->em])
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->schemaTool, $this->em, $this->connection, $this->configuration);
    }

    public function testGetUpdateSchemaSqlShouldAffectOnlyKnownDBTableParts()
    {
        list($fromSchema, $toSchema) = $this->prepareSchemas();

        $meta = [new \stdClass()];

        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->setMethods(['createSchema'])->disableOriginalConstructor()->getMockForAbstractClass();
        $schemaManager->expects($this->once())->method('createSchema')->willReturn($fromSchema);

        $this->connection->expects($this->once())->method('getSchemaManager')->willReturn($schemaManager);
        $this->schemaTool->expects($this->once())->method('getSchemaFromMetadata')->with($meta)->willReturn($toSchema);

        $this->assertEquals(
            ['DROP INDEX oro_idx_index_name ON oro_entity_extend_test_relation'],
            $this->schemaTool->getUpdateSchemaSql($meta, true)
        );
    }

    /**
     * @return Schema[]
     */
    protected function prepareSchemas()
    {
        Type::addType('int', 'Doctrine\DBAL\Types\IntegerType');

        $fromSchema = new Schema();
        $table      = $fromSchema->createTable('oro_entity_extend_test_table');
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
        $table    = $toSchema->createTable('oro_entity_extend_test_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $tableRelation = $toSchema->createTable('oro_entity_extend_test_relation');
        $tableRelation->addColumn('id', 'integer', ['autoincrement' => true]);
        $tableRelation->setPrimaryKey(['id']);

        return [$fromSchema, $toSchema];
    }
}
