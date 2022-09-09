<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateMaterializedView implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_materialized_view')) {
            return;
        }

        $table = $schema->createTable('oro_materialized_view');
        $table->addColumn('name', Types::STRING, ['length' => 63]);
        $table->setPrimaryKey(['name']);

        $table->addColumn('with_data', Types::BOOLEAN, ['default' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE, []);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, []);
    }
}
