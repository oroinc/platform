<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_12';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroEmailFolderImapTable($schema);
        $this->createOroEmailImapTable($schema);
        $this->createOroImapWrongCredsOriginTable($schema);

        /** Foreign keys generation **/
        $this->addOroEmailFolderImapForeignKeys($schema);
        $this->addOroEmailImapForeignKeys($schema);

        $this->updateOroEmailOriginTable($schema);
    }

    /**
     * Create oro_email_folder_imap table
     */
    private function createOroEmailFolderImapTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_folder_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer');
        $table->addColumn('uid_validity', 'integer');
        $table->addColumn('last_uid', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_EC4034F9162CB942');
    }

    /**
     * Create oro_email_imap table
     */
    private function createOroEmailImapTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer');
        $table->addColumn('uid', 'integer');
        $table->addColumn('imap_folder_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_17E00D83A832C1C9');
        $table->addIndex(['imap_folder_id'], 'IDX_17E00D834F00B133');
        $table->addIndex(['uid'], 'email_imap_uid_idx');
    }

    private function createOroImapWrongCredsOriginTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_imap_wrong_creds_origin');
        $table->addColumn('origin_id', 'integer', ['notnull' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['origin_id']);
        $table->addIndex(['owner_id']);
    }

    private function addOroEmailFolderImapForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_folder_imap');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroEmailImapForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_imap');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_imap'),
            ['imap_folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_17E00D834F00B133'
        );
    }

    private function updateOroEmailOriginTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('imap_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('imap_port', 'integer', ['notnull' => false]);
        $table->addColumn('imap_ssl', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('imap_user', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('imap_password', 'text', ['notnull' => false, 'length' => 16777216]);
        $table->addColumn('smtp_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('smtp_port', 'integer', ['notnull' => false]);
        $table->addColumn('smtp_encryption', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('access_token', 'text', ['notnull' => false, 'length' => 8192]);
        $table->addColumn('refresh_token', 'text', ['notnull' => false, 'length' => 8192]);
        $table->addColumn('access_token_expires_at', 'datetime', ['notnull' => false]);
        $table->addColumn('account_type', 'string', ['default' => 'other', 'notnull' => false, 'length'  => 255]);
    }
}
