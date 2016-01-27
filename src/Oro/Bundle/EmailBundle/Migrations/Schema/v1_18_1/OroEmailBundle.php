<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroEmailTable($schema);
        $this->deleteBodySyncProcess($queries);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        $table->addColumn('body_synced', 'boolean', ['notnull' => false, 'default' => false]);
    }

    /**
     * Delete sync_email_body_after_email_synchronize process definition
     *
     * @param QueryBag $queries
     */
    protected function deleteBodySyncProcess(QueryBag $queries)
    {
        $sql = <<<SQL
    DELETE
    FROM
      oro_process_definition
    WHERE name = 'sync_email_body_after_email_synchronize'
SQL;
        $queries->addPostQuery(new SqlMigrationQuery($sql));
    }
}
