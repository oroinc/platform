<?php

namespace Oro\Bundle\NavigationBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroNavigationBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_navigation_history **/
        $table = $schema->createTable('oro_navigation_history');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('url', 'string', ['default' => null, 'notnull' => true, 'length' => 1023, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('title', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('visited_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('visit_count', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_B20613B9A76ED395', []);
        /** End of generate table oro_navigation_history **/

        /** Generate table oro_navigation_item **/
        $table = $schema->createTable('oro_navigation_item');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => true, 'length' => 10, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('url', 'string', ['default' => null, 'notnull' => true, 'length' => 1023, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('title', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('position', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_323B0258A76ED395', []);
        $table->addIndex(['user_id', 'position'], 'sorted_items_idx', []);
        /** End of generate table oro_navigation_item **/

        /** Generate table oro_navigation_item_pinbar **/
        $table = $schema->createTable('oro_navigation_item_pinbar');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('item_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('maximized', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_54973433126F525E');
        /** End of generate table oro_navigation_item_pinbar **/

        /** Generate table oro_navigation_pagestate **/
        $table = $schema->createTable('oro_navigation_pagestate');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('page_id', 'string', ['default' => null, 'notnull' => true, 'length' => 4000, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('page_hash', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('data', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_hash'], 'UNIQ_8B43985B567C7E62');
        $table->addIndex(['user_id'], 'IDX_8B43985BA76ED395', []);
        /** End of generate table oro_navigation_pagestate **/

        /** Generate table oro_navigation_title **/
        $table = $schema->createTable('oro_navigation_title');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('route', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('title', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('short_title', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('is_system', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['route'], 'unq_route');
        /** End of generate table oro_navigation_title **/

        /** Generate foreign keys for table oro_navigation_history **/
        $table = $schema->getTable('oro_navigation_history');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_navigation_history **/

        /** Generate foreign keys for table oro_navigation_item **/
        $table = $schema->getTable('oro_navigation_item');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_navigation_item **/

        /** Generate foreign keys for table oro_navigation_item_pinbar **/
        $table = $schema->getTable('oro_navigation_item_pinbar');
        $table->addForeignKeyConstraint($schema->getTable('oro_navigation_item'), ['item_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_navigation_item_pinbar **/

        /** Generate foreign keys for table oro_navigation_pagestate **/
        $table = $schema->getTable('oro_navigation_pagestate');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_navigation_pagestate **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
