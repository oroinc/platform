<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_16;

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

    public static function createOroEmailMailboxUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_users');

        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('user_id', 'integer');

        $table->addUniqueIndex(['mailbox_id', 'user_id']);

        $table->addIndex(['mailbox_id']);
        $table->addIndex(['user_id']);
    }

    public static function createOroEmailMailboxRolesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_roles');

        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('role_id', 'integer');

        $table->addUniqueIndex(['mailbox_id', 'role_id']);

        $table->addIndex(['mailbox_id']);
        $table->addIndex(['role_id']);
    }

    /**
     * Adds foreign keys to tables: oro_email_mailbox_users and oro_email_mailbox_roles
     *
     * @param Schema $schema
     */
    public static function addOroEmailMailboxUsersAndRolesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_mailbox_users');

        $table->addForeignKeyConstraint('oro_email_mailbox', ['mailbox_id'], ['id']);
        $table->addForeignKeyConstraint('oro_user', ['user_id'], ['id']);

        $table = $schema->getTable('oro_email_mailbox_roles');

        $table->addForeignKeyConstraint('oro_email_mailbox', ['mailbox_id'], ['id']);
        $table->addForeignKeyConstraint('oro_access_role', ['role_id'], ['id']);
    }
}
