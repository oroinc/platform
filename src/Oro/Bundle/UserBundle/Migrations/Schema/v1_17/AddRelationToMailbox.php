<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddRelationToMailbox implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroEmailMailboxUsersTable($schema);
        self::createOroEmailMailboxRolesTable($schema);
        self::addOroEmailMailboxUsersAndRolesForeignKeys($schema);
    }

    /**
     * Creates 'oro_email_mailbox_users' table which represents relationship between mailboxes and authorized users.
     *
     * @param Schema $schema
     */
    public static function createOroEmailMailboxUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_users');
        $table->addColumn('mailbox_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['mailbox_id', 'user_id']);
        $table->addIndex(['mailbox_id'], 'IDX_F6E5635A66EC35CC', []);
        $table->addIndex(['user_id'], 'IDX_F6E5635AA76ED395', []);
    }

    /**
     * Creates 'oro_email_mailbox_roles' table which represents relationship between mailboxes and authorized roles.
     *
     * @param Schema $schema
     */
    public static function createOroEmailMailboxRolesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_roles');
        $table->addColumn('mailbox_id', 'integer', []);
        $table->addColumn('role_id', 'integer', []);
        $table->setPrimaryKey(['mailbox_id', 'role_id']);
        $table->addIndex(['mailbox_id'], 'IDX_5458E87466EC35CC', []);
        $table->addIndex(['role_id'], 'IDX_5458E874D60322AC', []);
    }

    /**
     * Adds foreign keys to 'oro_email_mailbox_users' and 'oro_email_mailbox_roles' tables.
     *
     * @param Schema $schema
     */
    public static function addOroEmailMailboxUsersAndRolesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_mailbox_roles');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_email_mailbox_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
