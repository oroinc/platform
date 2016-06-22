<?php
namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_1;

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
        $this->createTestSearchItem2Table($schema);
    }

    /**
     * Create test_search_item2 table
     *
     * @param Schema $schema
     */
    protected function createTestSearchItem2Table(Schema $schema)
    {
        $table = $schema->createTable('test_search_item2');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }
}
