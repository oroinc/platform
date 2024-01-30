<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroEmailMailboxTable($schema);
        $this->createOroEmailMailboxProcessSettingsTable($schema);
        $this->addOwnerMailboxColumn($schema);
        $this->addOroEmailMailboxForeignKeys($schema);
        $this->addEmailUserMailboxOwnerColumn($schema);
    }

    private function createOroEmailMailboxTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('process_settings_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'UNIQ_574C364FE7927C74');
        $table->addUniqueIndex(['label'], 'UNIQ_574C364FEA750E8');
        $table->addUniqueIndex(['process_settings_id'], 'UNIQ_574C364F37BAC19A');
        $table->addUniqueIndex(['origin_id'], 'UNIQ_574C364F56A273CC');
        $table->addIndex(['organization_id'], 'IDX_574C364F32C8A3DE');
    }

    private function createOroEmailMailboxProcessSettingsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_process');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    private function addOwnerMailboxColumn(Schema $schema): void
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

    private function addOroEmailMailboxForeignKeys(Schema $schema): void
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

    private function addEmailUserMailboxOwnerColumn(Schema $schema): void
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
