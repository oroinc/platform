<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
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
        $this->deleteBodySyncProcess($schema, $queries);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        if (!$table->hasColumn('body_synced')) {
            $table->addColumn('body_synced', 'boolean', ['notnull' => false, 'default' => false]);
        }
    }

    /**
     * Delete sync_email_body_after_email_synchronize process definition
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    protected function deleteBodySyncProcess(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_process_definition')) {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'DELETE FROM oro_process_definition WHERE name = :processName',
                    ['processName' => 'sync_email_body_after_email_synchronize']
                )
            );
        }
    }
}
