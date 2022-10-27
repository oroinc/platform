<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds notificationAlert table.
 */
class AddNotificationAlertTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroNotificationAlertTable($schema);
        $this->addOroNotificationAlertForeignKeys($schema);
    }

    private function createOroNotificationAlertTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_notification_alert');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', []);
        $table->addColumn('alert_type', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('source_type', 'string', ['length' => 50]);
        $table->addColumn('resource_type', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('operation', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('step', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('item_id', 'integer', ['notnull' => false]);
        $table->addColumn('external_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_resolved', 'boolean', ['default' => false, 'notnull' => true]);
        $table->addColumn('message', 'text', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'idx_ea4c646532c8a3de', []);
        $table->addIndex(['user_id'], 'idx_ea4c6465a76ed395', []);
        $table->setPrimaryKey(['id']);
    }

    private function addOroNotificationAlertForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_notification_alert');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
