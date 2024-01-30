<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email');
        if (!$table->hasColumn('body_synced')) {
            $table->addColumn('body_synced', 'boolean', ['notnull' => false, 'default' => false]);
        }

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
