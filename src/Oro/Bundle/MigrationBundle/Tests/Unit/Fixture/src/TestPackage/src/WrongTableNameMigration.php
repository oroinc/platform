<?php

namespace TestPackage\src;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class WrongTableNameMigration implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('extra_long_table_name_bigger_than_30_chars');
        $table->addColumn('id', 'integer');

        return [];
    }
}
