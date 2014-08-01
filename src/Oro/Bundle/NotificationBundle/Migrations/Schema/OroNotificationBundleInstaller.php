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
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroNotificationEmailNotifTable($schema);
        $this->createOroNotificationEventTable($schema);
        $this->createOroNotificationRecipListTable($schema);
        $this->createOroNotificationRecipUserTable($schema);
        $this->createOroNotificationRecipGroupTable($schema);
        $this->createOroNotificationEmailSpoolTable($schema);

        /** Foreign keys generation **/
        $this->addOroNotificationEmailNotifForeignKeys($schema);
        $this->addOroNotificationRecipUserForeignKeys($schema);
        $this->addOroNotificationRecipGroupForeignKeys($schema);
    }

    /**
     * Create oro_notification_email_notif table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEmailNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_email_notif');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('event_id', 'integer', ['notnull' => false]);
        $table->addColumn('recipient_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_name', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addIndex(['event_id'], 'IDX_A3D00FDF71F7E88B', []);
        $table->addIndex(['template_id'], 'IDX_A3D00FDF5DA0FB8', []);
        $table->addUniqueIndex(['recipient_list_id'], 'UNIQ_A3D00FDF2B9E3E89');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_event table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEventTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_event');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('description', 'text', ['notnull' => false, 'precision' => 0]);
        $table->addUniqueIndex(['name'], 'UNIQ_2E2482DF5E237E06');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_notification_recip_list table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationRecipListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_recip_list');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('owner', 'boolean', ['notnull' => false, 'precision' => 0]);
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
        $table->addIndex(['recipient_list_id'], 'IDX_606646402B9E3E89', []);
        $table->addIndex(['user_id'], 'IDX_60664640A76ED395', []);
        $table->setPrimaryKey(['recipient_list_id', 'user_id']);
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
        $table->addIndex(['recipient_list_id'], 'IDX_14F109F12B9E3E89', []);
        $table->addIndex(['group_id'], 'IDX_14F109F1FE54D947', []);
        $table->setPrimaryKey(['recipient_list_id', 'group_id']);
    }

    /**
     * Create oro_notification_email_spool table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationEmailSpoolTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_email_spool');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('status', 'integer', ['precision' => 0]);
        $table->addColumn('message', 'object', ['precision' => 0, 'comment' => '(DC2Type:object)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'notification_spool_status_idx', []);
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
            $schema->getTable('oro_notification_event'),
            ['event_id'],
            ['id'],
            []
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            []
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
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', ]
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
            $schema->getTable('oro_notification_recip_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', ]
        );
    }
}
