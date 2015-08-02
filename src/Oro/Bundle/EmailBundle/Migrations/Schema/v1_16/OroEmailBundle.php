<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroEmailMailboxTable($schema);
        self::createOroEmailMailboxUsersTable($schema);
        self::createOroEmailMailboxRolesTable($schema);
        self::createOroEmailMailboxProcessSettingsTable($schema);

        self::addOwnerMailboxColumn($schema);
        self::addOroEmailMailboxForeignKeys($schema);
        self::addOroEmailMailboxUsersAndRolesForeignKeys($schema);
        self::addEmailUserMailboxOwnerColumn($schema);
    }

    public static function createOroEmailMailboxTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('process_settings_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('smtp_settings', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['process_settings_id'], 'UNIQ_574C364F37BAC19A');
        $table->addUniqueIndex(['origin_id'], 'UNIQ_574C364F56A273CC');
        $table->addIndex(['organization_id'], 'IDX_574C364F32C8A3DE', []);
    }

    public static function createOroEmailMailboxProcessSettingsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_process');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    public static function addOwnerMailboxColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_email_address');

        $table->addColumn('owner_mailbox_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_mailbox_id'], 'IDX_FC9DBBC53486AC89');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['owner_mailbox_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_FC9DBBC53486AC89'
        );
    }

    /**
     * Add oro_email_mailbox foreign keys.
     *
     * @param Schema $schema
     */
    public static function addOroEmailMailboxForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_mailbox');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox_process'),
            ['process_settings_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    public static function addEmailUserMailboxOwnerColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addColumn('mailbox_owner_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
