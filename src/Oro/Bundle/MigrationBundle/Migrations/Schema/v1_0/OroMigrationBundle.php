<?php

namespace Oro\Bundle\MigrationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMigrationBundle implements Migration
{
    const MIGRATION_DATA_TABLE = 'oro_migrations_data';

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable(self::MIGRATION_DATA_TABLE);
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }
}
