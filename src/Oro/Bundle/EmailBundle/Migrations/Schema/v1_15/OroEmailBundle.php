<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
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
        $this->addEmailFolderFields($schema);
        $this->addEmailOriginFields($schema);
        $this->updateMailboxName($schema, $queries);
        $this->updateEmailRecipientConstraint($schema);
    }

    private function addEmailFolderFields(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_folder');
        $table->addColumn('sync_enabled', 'boolean', ['default' => false]);
        $table->addColumn('parent_folder_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $table,
            ['parent_folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_EB940F1C421FFFC'
        );
    }

    private function addEmailOriginFields(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('mailbox_name', 'string', ['length' => 64, 'notnull' => true, 'default' => '']);
        $table->addIndex(['mailbox_name'], 'IDX_mailbox_name', []);
    }

    private function updateMailboxName(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_origin');
        $sql = 'UPDATE oro_email_origin SET mailbox_name = %s WHERE name = :name';

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf($sql, "'" . InternalEmailOrigin::MAILBOX_NAME . "'"),
                ['name' => 'internalemailorigin'],
                ['name' => Types::STRING]
            )
        );

        if ($table->hasColumn('imap_user')) {
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'imap_user'),
                ['name' => 'imapemailorigin'],
                ['name' => Types::STRING]
            ));
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'imap_user'),
                ['name' => 'useremailorigin'],
                ['name' => Types::STRING]
            ));
        }

        if ($table->hasColumn('ews_user_email')) {
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'ews_user_email'),
                ['name' => 'ewsemailorigin'],
                ['name' => Types::STRING]
            ));
        }
    }

    private function updateEmailRecipientConstraint(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_recipient');
        $table->removeForeignKey('FK_7DAF9656A832C1C9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_7DAF9656A832C1C9'
        );
    }
}
