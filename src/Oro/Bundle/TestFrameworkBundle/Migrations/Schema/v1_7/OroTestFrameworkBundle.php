<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestNestedObjectsTable($schema);
    }

    /**
     * Create test_nested_objects table
     *
     * @param Schema $schema
     */
    public function createTestNestedObjectsTable(Schema $schema)
    {
        $table = $schema->createTable('test_nested_objects');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('related_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('related_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }
}
