<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add fields
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder');
        $table->addColumn('outdated_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['outdated_at'], 'email_folder_outdated_at_idx');

        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('sync_count', 'integer', ['notnull' => false]);
    }
}
