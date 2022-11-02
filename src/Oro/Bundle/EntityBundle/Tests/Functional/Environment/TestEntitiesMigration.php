<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_test_decimal_entity')) {
            return;
        }

        $this->createTestDecimalEntityTable($schema);
    }

    /**
     * Create oro_test_decimal_entity table
     */
    private function createTestDecimalEntityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_decimal_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn(
            'decimal_property',
            'decimal',
            ['notnull' => false, 'comment' => '(DC2Type:decimal)', 'precision' => 19, 'scale' => 4]
        );
        $table->setPrimaryKey(['id']);
    }
}
