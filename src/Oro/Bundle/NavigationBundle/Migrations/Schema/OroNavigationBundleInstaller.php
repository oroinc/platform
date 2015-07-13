<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroNavigationHistoryTable($schema);
        $this->createOroNavigationItemTable($schema);
        $this->createOroNavigationItemPinbarTable($schema);
        $this->createOroNavigationPageStateTable($schema);
        $this->createOroNavigationTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroNavigationHistoryForeignKeys($schema);
        $this->addOroNavigationItemForeignKeys($schema);
        $this->addOroNavigationItemPinbarForeignKeys($schema);
        $this->addOroNavigationPageStateForeignKeys($schema);
    }

    /**
     * Create oro_navigation_history table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('visited_at', 'datetime', []);
        $table->addColumn('visit_count', 'integer', []);
        $table->addColumn('route', 'string', ['length' => 128]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_B20613B9A76ED395', []);
        $table->addIndex(['route'], 'oro_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_navigation_history_entity_id_idx');
        $table->addIndex(['organization_id'], 'IDX_B20613B932C8A3DE', []);
    }

    /**
     * Create oro_navigation_item table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 10]);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_323B0258A76ED395', []);
        $table->addIndex(['user_id', 'position'], 'sorted_items_idx', []);
        $table->addIndex(['organization_id'], 'IDX_323B025832C8A3DE', []);
    }

    /**
     * Create oro_navigation_item_pinbar table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemPinbarTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_54973433126F525E');
    }

    /**
     * Create oro_navigation_pagestate table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationPageStateTable(Schema $schema)
    {
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
    }

    /**
     * Create oro_navigation_title table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('route', 'string', ['length' => 255]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('short_title', 'text', []);
        $table->addColumn('is_system', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['route'], 'unq_route');
    }

    /**
     * Add oro_navigation_history foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationHistoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_navigation_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_navigation_item_pinbar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationItemPinbarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_item_pinbar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_navigation_item'),
            ['item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_navigation_pagestate foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationPageStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_pagestate');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
