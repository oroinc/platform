<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schemas\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_notification_email_spool **/
        $table = $schema->createTable('oro_notification_email_spool');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('status', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('message', 'object', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'notification_spool_status_idx', []);
        /** End of generate table oro_notification_email_spool **/

        /** Generate table oro_notification_emailnotif **/
        $table = $schema->createTable('oro_notification_emailnotif');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('recipient_list_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('template_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('event_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['recipient_list_id'], 'UNIQ_F3D05A52B9E3E89');
        $table->addIndex(['event_id'], 'IDX_F3D05A571F7E88B', []);
        $table->addIndex(['template_id'], 'IDX_F3D05A55DA0FB8', []);
        /** End of generate table oro_notification_emailnotif **/

        /** Generate table oro_notification_event **/
        $table = $schema->createTable('oro_notification_event');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('description', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_2E2482DF5E237E06');
        /** End of generate table oro_notification_event **/

        /** Generate table oro_notification_recipient_grp **/
        $table = $schema->createTable('oro_notification_recipient_grp');
        $table->addColumn('recipient_list_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('group_id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['recipient_list_id', 'group_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_F6E3D23E2B9E3E89', []);
        $table->addIndex(['group_id'], 'IDX_F6E3D23EFE54D947', []);
        /** End of generate table oro_notification_recipient_grp **/

        /** Generate table oro_notification_recipient_lst **/
        $table = $schema->createTable('oro_notification_recipient_lst');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('email', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('owner', 'boolean', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_notification_recipient_lst **/

        /** Generate table oro_notification_recipient_usr **/
        $table = $schema->createTable('oro_notification_recipient_usr');
        $table->addColumn('recipient_list_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['recipient_list_id', 'user_id']);
        $table->addIndex(['recipient_list_id'], 'IDX_CAC79D892B9E3E89', []);
        $table->addIndex(['user_id'], 'IDX_CAC79D89A76ED395', []);
        /** End of generate table oro_notification_recipient_usr **/

        /** Generate foreign keys for table oro_notification_emailnotif **/
        $table = $schema->getTable('oro_notification_emailnotif');
        $table->addForeignKeyConstraint($schema->getTable('oro_notification_recipient_lst'), ['recipient_list_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_email_template'), ['template_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_notification_event'), ['event_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_notification_emailnotif **/

        /** Generate foreign keys for table oro_notification_recipient_grp **/
        $table = $schema->getTable('oro_notification_recipient_grp');
        $table->addForeignKeyConstraint($schema->getTable('oro_access_group'), ['group_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_notification_recipient_lst'), ['recipient_list_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_notification_recipient_grp **/

        /** Generate foreign keys for table oro_notification_recipient_usr **/
        $table = $schema->getTable('oro_notification_recipient_usr');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_notification_recipient_lst'), ['recipient_list_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_notification_recipient_usr **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
