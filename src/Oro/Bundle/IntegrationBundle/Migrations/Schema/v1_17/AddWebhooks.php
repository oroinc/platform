<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add oro_integration_webhook_producer_settings table
 * Add oro_integration_webhook_consumer_settings table
 * Add oro_integration_webhook_consumer_settings relation to oro_integration_transport table
 */
class AddWebhooks implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroIntegrationWebhookProducerSettingsTable($schema);
        $this->createOroIntegrationWebhookConsumerSettingsTable($schema);
        $this->addWebhookConsumerSettingsToTransport($schema);
    }

    private function createOroIntegrationWebhookProducerSettingsTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_integration_webhook_producer_settings')) {
            return;
        }

        $table = $schema->createTable('oro_integration_webhook_producer_settings');
        $table->addColumn('id', 'guid', ['notnull' => true]);
        $table->addColumn('notification_url', 'string', ['length' => 2048, 'notnull' => true]);
        $table->addColumn('topic', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn(
            'secret',
            'crypted_string',
            [
                'notnull' => false,
                'length' => 255,
                'comment' => '(DC2Type:crypted_string)'
            ]
        );
        $table->addColumn('format', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['default' => true, 'notnull' => true]);
        $table->addColumn('verify_ssl', 'boolean', ['default' => true, 'notnull' => true]);
        $table->addColumn('is_system', 'boolean', ['default' => false, 'notnull' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['topic', 'enabled'], 'idx_webhook_producer_settings_search');
        $table->addIndex(['user_owner_id'], 'idx_webhook_producer_settings_user_owner');
        $table->addIndex(['organization_id'], 'idx_webhook_producer_settings_organization');

        $this->addOroIntegrationWebhookProducerSettingsForeignKeys($schema);
    }

    private function createOroIntegrationWebhookConsumerSettingsTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_integration_webhook_consumer_settings')) {
            return;
        }

        $table = $schema->createTable('oro_integration_webhook_consumer_settings');
        $table->addColumn('id', 'guid', ['notnull' => true]);
        $table->addColumn('processor', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['default' => true, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    private function addOroIntegrationWebhookProducerSettingsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_webhook_producer_settings');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function addWebhookConsumerSettingsToTransport(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        if ($table->hasColumn('webhook_consumer_settings_id')) {
            return;
        }

        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('webhook_consumer_settings_id', 'guid', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_webhook_consumer_settings'),
            ['webhook_consumer_settings_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
