<?php

namespace Oro\Bundle\SidebarBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroSidebarBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_sidebar_state **/
        $table = $schema->createTable('oro_sidebar_state');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('position', 'string', ['default' => null, 'notnull' => true, 'length' => 13, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('state', 'string', ['default' => null, 'notnull' => true, 'length' => 17, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['user_id', 'position'], 'sidebar_state_unique_idx');
        $table->addIndex(['user_id'], 'IDX_AB2BC195A76ED395', []);
        /** End of generate table oro_sidebar_state **/

        /** Generate table oro_sidebar_widget **/
        $table = $schema->createTable('oro_sidebar_widget');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('placement', 'string', ['default' => null, 'notnull' => true, 'length' => 50, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('position', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('widget_name', 'string', ['default' => null, 'notnull' => true, 'length' => 50, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('settings', 'array', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('state', 'string', ['default' => null, 'notnull' => true, 'length' => 22, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_2FFBEA9CA76ED395', []);
        $table->addIndex(['user_id', 'placement'], 'sidebar_widgets_user_placement_idx', []);
        $table->addIndex(['position'], 'sidebar_widgets_position_idx', []);
        /** End of generate table oro_sidebar_widget **/

        /** Generate foreign keys for table oro_sidebar_state **/
        $table = $schema->getTable('oro_sidebar_state');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_sidebar_state **/

        /** Generate foreign keys for table oro_sidebar_widget **/
        $table = $schema->getTable('oro_sidebar_widget');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_sidebar_widget **/

        // @codingStandardsIgnoreEnd
    }
}
