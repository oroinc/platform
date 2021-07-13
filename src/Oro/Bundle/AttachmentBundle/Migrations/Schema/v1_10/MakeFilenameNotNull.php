<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Make filename NOT NULL. Remove broken records where filename IS NULL
 */
class MakeFilenameNotNull implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_attachment_file');

        // filename column is already NOT NULL, do nothing
        if (!$table->getColumn('filename')->getNotnull()) {
            $queries->addPreQuery(new ParametrizedSqlMigrationQuery(<<<SQL
                 DELETE FROM oro_attachment 
                 WHERE file_id IN (SELECT id FROM oro_attachment_file WHERE filename IS NULL)
            SQL));

            $queries->addPreQuery(new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_attachment_file WHERE filename IS NULL'
            ));
            $table->getColumn('filename')->setNotnull(true);
        }
    }
}
