<?php

namespace Oro\Bundle\ReminderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroReminderBundleBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_3';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createReminderTable($schema);

        /** Foreign keys generation **/
        $this->addReminderForeignKeys($schema);
    }

    private function createReminderTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_reminder');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('recipient_id', 'integer', ['notnull' => false]);
        $table->addColumn('subject', 'string', ['length' => 255]);
        $table->addColumn('start_at', 'datetime');
        $table->addColumn('expire_at', 'datetime');
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('interval_number', 'integer');
        $table->addColumn('interval_unit', 'string', ['length' => 1]);
        $table->addColumn('state', 'string', ['length' => 32]);
        $table->addColumn('related_entity_id', 'integer');
        $table->addColumn('related_entity_classname', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('sent_at', 'datetime', ['notnull' => false]);
        $table->addColumn('failure_exception', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('sender_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['recipient_id'], 'IDX_2F4F9F57E92F8F78');
        $table->addIndex(['state'], 'reminder_state_idx');
        $table->addIndex(['sender_id'], 'idx_2f4f9f57f624b39d');
    }

    private function addReminderForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_reminder');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['recipient_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['sender_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
