<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ImapBundle\Migrations\Schema\v1_3\OroImapBundle as v13;
use Oro\Bundle\ImapBundle\Migrations\Schema\v1_4\OroImapBundle as v14;

class OroImapBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
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

        /** Foreign keys generation **/
        $this->addOroEmailFolderImapForeignKeys($schema);
        $this->addOroEmailImapForeignKeys($schema);
    }

    /**
     * Add Imap fields to the oro_email_origin table
     *
     * @param Schema $schema
     */
    protected function addImapFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('imap_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('imap_port', 'integer', ['notnull' => false]);
        $table->addColumn('imap_ssl', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('imap_user', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('imap_password', 'string', ['notnull' => false, 'length' => 100]);
    }

    /**
     * Create oro_email_folder_imap table
     *
     * @param Schema $schema
     */
    protected function createOroEmailFolderImapTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_folder_imap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('uid_validity', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_EC4034F9162CB942');
    }

    /**
     * Create oro_email_imap table
     *
     * @param Schema $schema
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
    }

    /**
     * Add oro_email_folder_imap foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEmailFolderImapForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder_imap');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_imap foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEmailImapForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_imap');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_imap'),
            ['imap_folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_17E00D834F00B133'
        );
    }
}
