<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration depends from v1_15/UpdateEmailOriginTableQuery update.
 * Migration can not be moved to EmailBundle.
 * Turning on sync to all user folders with emails
 */
class UpdateEmailFolderSyncState implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');

        if ($table->hasColumn('imap_user')) {
            $queries->addPostQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE oro_email_folder AS ef SET sync_enabled = :sync WHERE ef.id IN (
                        SELECT eu.folder_id FROM oro_email_user AS eu WHERE eu.folder_id = ef.id GROUP BY eu.folder_id
                    ) AND ef.origin_id IN (
                        SELECT eo.id FROM oro_email_origin AS eo WHERE eo.id = ef.origin_id
                            AND (eo.name = :name_old OR eo.name = :name_new) GROUP BY eo.id
                    );
                ',
                    ['sync' => true, 'name_old' => 'imapemailorigin', 'name_new' => 'useremailorigin'],
                    ['sync' => Type::BOOLEAN, 'name_old' => Type::STRING, 'name_new' => Type::STRING]
                )
            );
        }
    }
}
