<?php

namespace Oro\Bundle\SidebarBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSidebarBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_sidebar_state **/
        $table = $schema->createTable('oro_sidebar_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['user_id', 'position'], 'sidebar_state_unique_idx');
        $table->addIndex(['user_id'], 'IDX_AB2BC195A76ED395', []);
        /** End of generate table oro_sidebar_state **/

        /** Generate table oro_sidebar_widget **/
        $table = $schema->createTable('oro_sidebar_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false]);
        $table->addColumn('state', 'string', ['length' => 22]);
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
