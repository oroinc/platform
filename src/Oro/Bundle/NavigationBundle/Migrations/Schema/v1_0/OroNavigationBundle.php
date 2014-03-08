<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle extends Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_navigation_history **/
        $table = $schema->createTable('oro_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('visited_at', 'datetime', []);
        $table->addColumn('visit_count', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_B20613B9A76ED395', []);
        /** End of generate table oro_navigation_history **/

        /** Generate table oro_navigation_item **/
        $table = $schema->createTable('oro_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 10]);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_323B0258A76ED395', []);
        $table->addIndex(['user_id', 'position'], 'sorted_items_idx', []);
        /** End of generate table oro_navigation_item **/

        /** Generate table oro_navigation_item_pinbar **/
        $table = $schema->createTable('oro_navigation_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_54973433126F525E');
        /** End of generate table oro_navigation_item_pinbar **/

        /** Generate table oro_navigation_pagestate **/
        $table = $schema->createTable('oro_navigation_pagestate');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('page_id', 'string', ['length' => 4000]);
        $table->addColumn('page_hash', 'string', ['length' => 32]);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_hash'], 'UNIQ_8B43985B567C7E62');
        $table->addIndex(['user_id'], 'IDX_8B43985BA76ED395', []);
        /** End of generate table oro_navigation_pagestate **/

        /** Generate table oro_navigation_title **/
        $table = $schema->createTable('oro_navigation_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('route', 'string', ['length' => 255]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('short_title', 'string', ['length' => 255]);
        $table->addColumn('is_system', 'boolean', []);
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
    }
}
