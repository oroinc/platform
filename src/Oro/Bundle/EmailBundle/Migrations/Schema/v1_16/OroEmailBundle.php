<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroEmailMailboxTable($schema);
        self::createOroEmailMailboxProcessSettingsTable($schema);

        self::addOwnerMailboxColumn($schema);
        self::addOroEmailMailboxForeignKeys($schema);
        self::addEmailUserMailboxOwnerColumn($schema);
    }

    /**
     * Creates 'oro_email_mailbox' table which represents Mailbox entity.
     *
     * @param Schema $schema
     */
    public static function createOroEmailMailboxTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('process_settings_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'UNIQ_574C364FE7927C74');
        $table->addUniqueIndex(['label'], 'UNIQ_574C364FEA750E8');
        $table->addUniqueIndex(['process_settings_id'], 'UNIQ_574C364F37BAC19A');
        $table->addUniqueIndex(['origin_id'], 'UNIQ_574C364F56A273CC');
        $table->addIndex(['organization_id'], 'IDX_574C364F32C8A3DE', []);
    }

    /**
     * Creates 'oro_email_mailbox_process' table which represents MailboxProcessSettings entity.
     * A common shared mapped superclass for all mailbox process settings types.
     *
     * @param Schema $schema
     */
    public static function createOroEmailMailboxProcessSettingsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_process');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Adds mailbox owner to EmailUser entity.
     *
     * @param Schema $schema
     */
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
     * Add 'oro_email_mailbox' table foreign keys.
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

    /**
     * Adds foreign keys to new columns in 'oro_email_user' table.
     *
     * @param Schema $schema
     */
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
