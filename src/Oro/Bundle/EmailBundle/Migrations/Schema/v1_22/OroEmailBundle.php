<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroEmailFolderTable($schema);
        $this->updateSyncStart($schema, $queries);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailFolderTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder');
        if (!$table->hasColumn('sync_start_date')) {
            $table->addColumn('sync_start_date', 'datetime', ['notnull' => false]);
        }
    }


    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    protected function updateSyncStart(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_email_folder')) {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE oro_email_folder SET sync_start_date = synchronized'
                )
            );
        }
    }
}
