<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addEmailFolderFields($schema);
        self::addEmailOriginFields($schema);

        $this->updateMailboxName($schema, $queries);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmailFolderFields(Schema $schema)
    {
        $emailFolderTable = $schema->getTable('oro_email_folder');

        $emailFolderTable->addColumn('sync_enabled', 'boolean', ['default' => false]);
        $emailFolderTable->addColumn('parent_folder_id', 'integer', ['notnull' => false]);
        $emailFolderTable->addForeignKeyConstraint(
            $emailFolderTable,
            ['parent_folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_EB940F1C421FFFC'
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmailOriginFields(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');

        $table->addColumn('mailbox_name', 'string', ['length' => 64, 'notnull' => true]);
        $table->addIndex(['mailbox_name'], 'IDX_mailbox_name', []);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function updateMailboxName(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');
        $queries->addPostQuery('UPDATE oro_email_origin SET mailbox_name = internal_name
            WHERE
                mailbox_name = ""
                AND internal_name IS NOT NULL

            ');
        if ($table->hasColumn('imap_user')) {
            $queries->addPostQuery('UPDATE oro_email_origin SET mailbox_name = imap_user
                WHERE
                    mailbox_name = ""
                    AND imap_user IS NOT NULL
                ');
        }
        if ($table->hasColumn('ews_user_email')) {
            $queries->addPostQuery('UPDATE oro_email_origin SET mailbox_name = ews_user_email
                WHERE
                    mailbox_name = ""
                    AND ews_user_email IS NOT NULL
                ');
        }
    }
}
