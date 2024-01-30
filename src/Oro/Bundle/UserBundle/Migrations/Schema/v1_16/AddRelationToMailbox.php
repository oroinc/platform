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
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroEmailMailboxUsersTable($schema);
        $this->createOroEmailMailboxRolesTable($schema);
    }

    private function createOroEmailMailboxUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_users');
        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['mailbox_id', 'user_id']);
        $table->addIndex(['mailbox_id'], 'IDX_F6E5635A66EC35CC');
        $table->addIndex(['user_id'], 'IDX_F6E5635AA76ED395');
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

    private function createOroEmailMailboxRolesTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_roles');
        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('role_id', 'integer');
        $table->setPrimaryKey(['mailbox_id', 'role_id']);
        $table->addIndex(['mailbox_id'], 'IDX_5458E87466EC35CC');
        $table->addIndex(['role_id'], 'IDX_5458E874D60322AC');
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
    }
}
