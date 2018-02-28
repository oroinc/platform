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
        return 'v1_8';
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
        $this->createOroNavigationMenuUpdateTable($schema);
        $this->createOroNavigationMenuUpdateTitleTable($schema);
        $this->createOroNavigationMenuUpdateDescriptionTable($schema);

        /** Foreign keys generation **/
        $this->addOroNavigationHistoryForeignKeys($schema);
        $this->addOroNavigationItemForeignKeys($schema);
        $this->addOroNavigationItemPinbarForeignKeys($schema);
        $this->addOroNavigationPageStateForeignKeys($schema);
        $this->addOroNavigationMenuUpdateTitleForeignKeys($schema);
        $this->addOroNavigationMenuUpdateDescriptionForeignKeys($schema);
        $this->addOroNavigationMenuUpdateForeignKeys($schema);
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
        $table->addIndex(['user_id', 'organization_id'], 'oro_navigation_history_user_org_idx', []);
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
     * Create oro_navigation_menu_upd
     *
     * @param Schema $schema
     */
    protected function createOroNavigationMenuUpdateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_menu_upd');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_key', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 1023, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('icon', 'string', ['length' => 150, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['key', 'scope_id', 'menu'], 'oro_navigation_menu_upd_uidx');
    }

    /**
     * Create oro_navigation_menu_upd_title table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationMenuUpdateTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_menu_upd_title');
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }


    /**
     * Create `oro_navigation_menu_upd_descr` table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationMenuUpdateDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_navigation_menu_upd_descr');
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
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

    /**
     * Add oro_navigation_menu_upd_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationMenuUpdateTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_navigation_menu_upd'),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add `oro_navigation_menu_upd_descr` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationMenuUpdateDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_navigation_menu_upd'),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add `oro_navigation_menu_upd` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationMenuUpdateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id']
        );
    }
}
