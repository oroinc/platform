<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        self::oroNotificationEmailSpoolTable($schema);
        self::oroNotificationEmailnotificationTable($schema);
        self::oroNotificationEventTable($schema);
        self::oroNotificationRecipientGroupTable($schema);
        self::oroNotificationRecipientListTable($schema);
        self::oroNotificationRecipientUserTable($schema);

        self::oroNotificationEmailnotificationForeignKeys($schema);
        self::oroNotificationRecipientGroupForeignKeys($schema);
        self::oroNotificationRecipientUserForeignKeys($schema);

        return [];
    }

    /**
     * Generate table oro_notification_email_spool
     *
     * @param Schema $schema
     */
    public static function oroNotificationEmailSpoolTable(Schema $schema)
    {
        /** Generate table oro_notification_email_spool **/
        $table = $schema->createTable('oro_notification_email_spool');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('message', 'object', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'notification_spool_status_idx', []);
        /** End of generate table oro_notification_email_spool **/
    }

    /**
     * Generate table oro_notification_emailnotification
     *
     * @param Schema $schema
     * @param string $emailNotif
     */
    public static function oroNotificationEmailnotificationTable(Schema $schema, $emailNotif = '')
    {
        /** Generate table oro_notification_emailnotification **/
        $table = $schema->createTable($emailNotif ? : 'oro_notification_emailnotification');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('recipient_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('event_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['recipient_list_id'], 'UNIQ_F3D05A52B9E3E89');
        $table->addIndex(['event_id'], 'IDX_F3D05A571F7E88B', []);
        $table->addIndex(['template_id'], 'IDX_F3D05A55DA0FB8', []);
        /** End of generate table oro_notification_emailnotification **/
    }

    /**
     * Generate table oro_notification_event
     *
     * @param Schema $schema
     */
    public static function oroNotificationEventTable(Schema $schema)
    {
        /** Generate table oro_notification_event **/
        $table = $schema->createTable('oro_notification_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_2E2482DF5E237E06');
        /** End of generate table oro_notification_event **/
    }

    /**
     * Generate table oro_notification_recipient_group
     *
     * @param Schema $schema
     * @param string $recipientGroup
     */
    public static function oroNotificationRecipientGroupTable(Schema $schema, $recipientGroup = '')
    {
        /** Generate table oro_notification_recipient_group **/
        $table = $schema->createTable($recipientGroup ? : 'oro_notification_recipient_group');
        $table->addColumn('recipient_list_id', 'integer', []);
        $table->addColumn('group_id', 'smallint', []);
        $table->setPrimaryKey(['recipient_list_id', 'group_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_F6E3D23E2B9E3E89', []);
        $table->addIndex(['group_id'], 'IDX_F6E3D23EFE54D947', []);
        /** End of generate table oro_notification_recipient_group **/
    }

    /**
     * Generate table oro_notification_recipient_list
     *
     * @param Schema $schema
     * @param string $recipientList
     */
    public static function oroNotificationRecipientListTable(Schema $schema, $recipientList = '')
    {
        /** Generate table oro_notification_recipient_list **/
        $table = $schema->createTable($recipientList ? : 'oro_notification_recipient_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('owner', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_notification_recipient_list **/
    }

    /**
     * Generate table oro_notification_recipient_user
     *
     * @param Schema $schema
     * @param string $recipientUser
     */
    public static function oroNotificationRecipientUserTable(Schema $schema, $recipientUser = '')
    {
        /** Generate table oro_notification_recipient_user **/
        $table = $schema->createTable($recipientUser ? : 'oro_notification_recipient_user');
        $table->addColumn('recipient_list_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['recipient_list_id', 'user_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_CAC79D892B9E3E89', []);
        $table->addIndex(['user_id'], 'IDX_CAC79D89A76ED395', []);
        /** End of generate table oro_notification_recipient_user **/
    }

    /**
     * Generate foreign keys for table oro_notification_emailnotification
     *
     * @param Schema $schema
     * @param string $emailNotif
     * @param string $recipientList
     */
    public static function oroNotificationEmailnotificationForeignKeys(
        Schema $schema,
        $emailNotif = '',
        $recipientList = ''
    ) {
        /** Generate foreign keys for table oro_notification_emailnotification **/
        $table = $schema->getTable($emailNotif ? : 'oro_notification_emailnotification');
        $table->addForeignKeyConstraint(
            $schema->getTable($recipientList ? : 'oro_notification_recipient_list'),
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
        /** End of generate foreign keys for table oro_notification_emailnotification **/
    }

    /**
     * Generate foreign keys for table oro_notification_recipient_group
     *
     * @param Schema $schema
     * @param string $recipientGroup
     * @param string $recipientList
     */
    public static function oroNotificationRecipientGroupForeignKeys(
        Schema $schema,
        $recipientGroup = '',
        $recipientList = ''
    ) {
        /** Generate foreign keys for table oro_notification_recipient_group **/
        $table = $schema->getTable($recipientGroup ? : 'oro_notification_recipient_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable($recipientList ? : 'oro_notification_recipient_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_notification_recipient_group **/
    }

    /**
     * Generate foreign keys for table oro_notification_recipient_user
     *
     * @param Schema $schema
     * @param string $recipientUser
     * @param string $recipientList
     */
    public static function oroNotificationRecipientUserForeignKeys(
        Schema $schema,
        $recipientUser = '',
        $recipientList = ''
    ) {
        /** Generate foreign keys for table oro_notification_recipient_user **/
        $table = $schema->getTable($recipientUser ? : 'oro_notification_recipient_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable($recipientList ? : 'oro_notification_recipient_list'),
            ['recipient_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_notification_recipient_user **/
    }
}
