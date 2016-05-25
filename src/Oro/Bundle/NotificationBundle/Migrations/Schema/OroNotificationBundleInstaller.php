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
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroNotificationEmailNotifTable($schema);
        $this->createOroNotificationEmailSpoolTable($schema);
        $this->createOroNotificationEventTable($schema);
        $this->createOroNotificationMassNotifTable($schema);
        $this->createOroNotificationRecipGroupTable($schema);
        $this->createOroNotificationRecipListTable($schema);
        $this->createOroNotificationRecipUserTable($schema);

        /** Foreign keys generation **/
        $this->addOroNotificationEmailNotifForeignKeys($schema);
        $this->addOroNotificationRecipGroupForeignKeys($schema);
        $this->addOroNotificationRecipUserForeignKeys($schema);
    }

    /**
     * Create oro_notification_email_notif table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEmailNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_email_notif');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('recipient_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('event_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['recipient_list_id'], 'UNIQ_A3D00FDF2B9E3E89');
        $table->addIndex(['event_id'], 'IDX_A3D00FDF71F7E88B', []);
        $table->addIndex(['template_id'], 'IDX_A3D00FDF5DA0FB8', []);
    }

    /**
     * Create oro_notification_email_spool table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEmailSpoolTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_email_spool');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('message', 'object', ['comment' => '(DC2Type:object)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'notification_spool_status_idx', []);
    }

    /**
     * Create oro_notification_event table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEventTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_2E2482DF5E237E06');
    }

    /**
     * Create oro_notification_mass_notif table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationMassNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_mass_notif');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('sender', 'string', ['length' => 255]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('body', 'text', ['notnull' => false]);
        $table->addColumn('scheduledAt', 'datetime', []);
        $table->addColumn('processedAt', 'datetime', []);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('message', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_recip_group table
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
     */
    protected function createOroNotificationRecipListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_recip_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('owner', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_recip_user table
     *
     * @param Schema $schema
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
     * Add oro_notification_email_notif foreign keys.
     *
     * @param Schema $schema
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_notification_event'),
            ['event_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_notification_recip_group foreign keys.
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
}
