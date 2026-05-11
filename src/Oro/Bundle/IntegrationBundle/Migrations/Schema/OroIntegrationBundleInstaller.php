<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroIntegrationBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_17';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroIntegrationFieldsChangesTable($schema);
        $this->createOroIntegrationChannelTable($schema);
        $this->createOroIntegrationChannelStatusTable($schema);
        $this->createOroIntegrationTransportTable($schema);
        $this->createOroIntegrationWebhookProducerSettingsTable($schema);
        $this->createOroIntegrationWebhookConsumerSettingsTable($schema);

        /** Foreign keys generation **/
        $this->addOroIntegrationChannelForeignKeys($schema);
        $this->addOroIntegrationChannelStatusForeignKeys($schema);
        $this->addOroIntegrationTransportForeignKeys($schema);
        $this->addOroIntegrationWebhookProducerSettingsForeignKeys($schema);
    }

    private function createOroIntegrationFieldsChangesTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_integration_fields_changes');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('changed_fields', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_integration_fields_changes_idx');
    }

    private function createOroIntegrationChannelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_integration_channel');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('transport_id', 'integer', ['notnull' => false]);
        $table->addColumn('default_user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('connectors', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('synchronization_settings', 'config_object', ['comment' => '(DC2Type:config_object)']);
        $table->addColumn('mapping_settings', 'config_object', ['comment' => '(DC2Type:config_object)']);
        $table->addColumn('enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('edit_mode', 'integer', ['notnull' => true, 'default' => Channel::EDIT_MODE_ALLOW]);
        $table->addColumn('default_business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['transport_id'], 'UNIQ_55B9B9C59909C13F');
        $table->addIndex(['default_user_owner_id'], 'IDX_55B9B9C5A89019EA');
        $table->addIndex(['organization_id'], 'IDX_55B9B9C532C8A3DE');
        $table->addIndex(['name'], 'oro_integration_channel_name_idx');
        $table->addIndex(['default_business_unit_owner_id'], 'IDX_55B9B9C5FA248E2');
        $table->addColumn('previously_enabled', 'boolean', ['notnull' => false]);
    }

    private function createOroIntegrationChannelStatusTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_integration_channel_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('connector', 'string', ['length' => 255]);
        $table->addColumn('message', 'text');
        $table->addColumn('date', 'datetime');
        $table->addColumn('data', Types::JSON, ['notnull' => false, 'comment' => '(DC2Type:json)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_C0D7E5FB72F5A1AA');
        $table->addIndex(['date'], 'oro_intch_date_idx');
        $table->addIndex(['connector', 'code'], 'oro_intch_con_state_idx');
    }

    private function createOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_integration_transport');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->addColumn('webhook_consumer_settings_id', 'guid', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'oro_int_trans_type_idx');
    }

    private function createOroIntegrationWebhookProducerSettingsTable(Schema $schema): void
    {
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
    }

    private function createOroIntegrationWebhookConsumerSettingsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_integration_webhook_consumer_settings');
        $table->addColumn('id', 'guid', ['notnull' => true]);
        $table->addColumn('processor', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['default' => true, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    private function addOroIntegrationChannelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['default_user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['default_business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function addOroIntegrationChannelStatusForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_channel_status');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroIntegrationTransportForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_webhook_consumer_settings'),
            ['webhook_consumer_settings_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
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
}
