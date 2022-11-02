<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroNotificationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_7';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroNotificationEmailNotifTable($schema);
        $this->createOroNotificationMassNotifTable($schema);
        $this->createOroNotificationRecipGroupTable($schema);
        $this->createOroNotificationRecipListTable($schema);
        $this->createOroNotificationRecipUserTable($schema);
        $this->createOroNotificationAlertTable($schema);

        /** Foreign keys generation **/
        $this->addOroNotificationEmailNotifForeignKeys($schema);
        $this->addOroNotificationRecipGroupForeignKeys($schema);
        $this->addOroNotificationRecipUserForeignKeys($schema);
        $this->addOroNotificationAlertForeignKeys($schema);
    }

    /**
     * Create oro_notification_email_notif table
     */
    protected function createOroNotificationEmailNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_email_notif');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('recipient_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('event_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('entity_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['recipient_list_id'], 'UNIQ_A3D00FDF2B9E3E89');
        $table->addIndex(['template_id'], 'IDX_A3D00FDF5DA0FB8', []);
    }

    /**
     * Create oro_notification_mass_notif table
     */
    protected function createOroNotificationMassNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_mass_notif');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('sender', 'string', ['length' => 255]);
        $table->addColumn('subject', 'string', ['length' => 255]);
        $table->addColumn('body', 'text', ['notnull' => false]);
        $table->addColumn('scheduledAt', 'datetime', []);
        $table->addColumn('processedAt', 'datetime', []);
        $table->addColumn('status', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_recip_group table
     */
    protected function createOroNotificationRecipGroupTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_recip_group');
        $table->addColumn('recipient_list_id', 'integer', []);
        $table->addColumn('group_id', 'integer', []);
        $table->setPrimaryKey(['recipient_list_id', 'group_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_14F109F12B9E3E89', []);
        $table->addIndex(['group_id'], 'IDX_14F109F1FE54D947', []);
    }

    /**
     * Create oro_notification_recip_list table
     */
    protected function createOroNotificationRecipListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_recip_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'additional_email_associations',
            'simple_array',
            [
                'comment' => '(DC2Type:simple_array)',
                'notnull' => false
            ]
        );
        $table->addColumn(
            'entity_emails',
            'simple_array',
            [
                'comment' => '(DC2Type:simple_array)',
                'notnull' => false
            ]
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_recip_user table
     */
    protected function createOroNotificationRecipUserTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_recip_user');
        $table->addColumn('recipient_list_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['recipient_list_id', 'user_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_606646402B9E3E89', []);
        $table->addIndex(['user_id'], 'IDX_60664640A76ED395', []);
    }

    /**
     * Create oro_notification_alert table
     */
    protected function createOroNotificationAlertTable(Schema $schema): void
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
        $table->addColumn('operation', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('step', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('item_id', 'integer', ['notnull' => false]);
        $table->addColumn('external_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_resolved', 'boolean', ['default' => false, 'notnull' => true]);
        $table->addColumn('message', 'text', ['notnull' => false]);
        $table->addColumn('additional_info', 'json', ['notnull' => false, 'comment' => '(DC2Type:json)']);
        $table->addIndex(['organization_id'], 'idx_ea4c646532c8a3de', []);
        $table->addIndex(['user_id'], 'idx_ea4c6465a76ed395', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_notification_email_notif foreign keys.
     */
    protected function addOroNotificationEmailNotifForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_email_notif');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_notification_recip_group foreign keys.
     */
    protected function addOroNotificationRecipGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_recip_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_notification_recip_user foreign keys.
     */
    protected function addOroNotificationRecipUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_recip_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_notification_alert foreign keys.
     */
    protected function addOroNotificationAlertForeignKeys(Schema $schema): void
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
