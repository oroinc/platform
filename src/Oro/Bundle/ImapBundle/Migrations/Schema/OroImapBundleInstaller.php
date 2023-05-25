<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ImapBundle\Migrations\Schema\v1_3\OroImapBundle as v13;
use Oro\Bundle\ImapBundle\Migrations\Schema\v1_4\OroImapBundle as v14;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * ORO installer for ImapBundle
 */
class OroImapBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_11';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->addImapFieldsToOroEmailOriginTable($schema);
        $this->createOroEmailFolderImapTable($schema);
        $this->createOroEmailImapTable($schema);
        v13::addSmtpFieldsToOroEmailOriginTable($schema);
        v14::addAccessTokenFieldsToOroEmailOriginTable($schema);
        $this->addOroImapWrongCredsOriginTable($schema);

        /** Foreign keys generation **/
        $this->addOroEmailFolderImapForeignKeys($schema);
        $this->addOroEmailImapForeignKeys($schema);
        $this->addOriginOauthType($schema);
        $this->changeEmailTokensLength($schema);
    }

    private function changeEmailTokensLength(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');

        $table->changeColumn('access_token', ['type' => TextType::getType(Types::TEXT), 'length' => 8192]);
        $table->changeColumn('refresh_token', ['type' => TextType::getType(Types::TEXT), 'length' => 8192]);
    }

    /**
     * Add Imap fields to the oro_email_origin table
     */
    protected function addImapFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('imap_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('imap_port', 'integer', ['notnull' => false]);
        $table->addColumn('imap_ssl', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('imap_user', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('imap_password', 'text', ['notnull' => false, 'length' => 16777216]);
    }

    /**
     * Create oro_email_folder_imap table
     */
    protected function createOroEmailFolderImapTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_folder_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('uid_validity', 'integer', []);
        $table->addColumn('last_uid', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_EC4034F9162CB942');
    }

    /**
     * Create oro_email_imap table
     */
    protected function createOroEmailImapTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('uid', 'integer', []);
        $table->addColumn('imap_folder_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_17E00D83A832C1C9', []);
        $table->addIndex(['imap_folder_id'], 'IDX_17E00D834F00B133', []);
        $table->addIndex(['uid'], 'email_imap_uid_idx');
    }

    /**
     * Add oro_email_folder_imap foreign keys.
     */
    protected function addOroEmailFolderImapForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder_imap');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_imap foreign keys.
     */
    protected function addOroEmailImapForeignKeys(Schema $schema)
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

    /**
     * Add oro_imap_wrong_creds_origin table.
     */
    protected function addOroImapWrongCredsOriginTable(Schema $schema)
    {
        $table = $schema->createTable('oro_imap_wrong_creds_origin');
        $table->addColumn('origin_id', 'integer', ['notnull' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['origin_id']);
        $table->addIndex(['owner_id']);
    }

    protected function addOriginOauthType(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('account_type', 'string', [
            'default' => 'other',
            'notnull' => false,
            'length'  => 255
        ]);
    }
}
