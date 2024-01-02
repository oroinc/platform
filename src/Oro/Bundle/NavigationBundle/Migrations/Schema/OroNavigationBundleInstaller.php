<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_12';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
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
     */
    private function createOroNavigationHistoryTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('url', 'string', ['length' => 8190]);
        $table->addColumn('title', 'text');
        $table->addColumn('visited_at', 'datetime');
        $table->addColumn('visit_count', 'integer');
        $table->addColumn('route', 'string', ['length' => 128]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_B20613B9A76ED395');
        $table->addIndex(['route'], 'oro_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_navigation_history_entity_id_idx');
        $table->addIndex(['organization_id'], 'IDX_B20613B932C8A3DE');
        $table->addIndex(['user_id', 'organization_id'], 'oro_navigation_history_user_org_idx');
    }

    /**
     * Create oro_navigation_item table
     */
    private function createOroNavigationItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 10]);
        $table->addColumn('url', 'string', ['length' => 8190]);
        $table->addColumn('title', 'text');
        $table->addColumn('position', 'smallint');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_323B0258A76ED395');
        $table->addIndex(['user_id', 'position'], 'sorted_items_idx');
        $table->addIndex(['organization_id'], 'IDX_323B025832C8A3DE');
    }

    /**
     * Create oro_navigation_item_pinbar table
     */
    private function createOroNavigationItemPinbarTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer');
        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('title_short', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_54973433126F525E');
    }

    /**
     * Create oro_navigation_pagestate table
     */
    private function createOroNavigationPageStateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_pagestate');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('page_id', 'string', ['length' => 10920]);
        $table->addColumn('page_hash', 'string', ['length' => 32]);
        $table->addColumn('data', 'text');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_hash'], 'UNIQ_8B43985B567C7E62');
        $table->addIndex(['user_id'], 'IDX_8B43985BA76ED395');
    }

    /**
     * Create oro_navigation_menu_upd
     */
    private function createOroNavigationMenuUpdateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_menu_upd');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_key', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 8190, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('icon', 'string', ['length' => 150, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean');
        $table->addColumn('is_divider', 'boolean');
        $table->addColumn('is_custom', 'boolean');
        $table->addColumn('is_synthetic', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['key', 'scope_id', 'menu'], 'oro_navigation_menu_upd_uidx');
    }

    /**
     * Create oro_navigation_menu_upd_title table
     */
    private function createOroNavigationMenuUpdateTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_menu_upd_title');
        $table->addColumn('menu_update_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create `oro_navigation_menu_upd_descr` table
     */
    private function createOroNavigationMenuUpdateDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_navigation_menu_upd_descr');
        $table->addColumn('menu_update_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_navigation_history foreign keys.
     */
    private function addOroNavigationHistoryForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationItemForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationItemPinbarForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationPageStateForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationMenuUpdateTitleForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationMenuUpdateDescriptionForeignKeys(Schema $schema): void
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
     */
    private function addOroNavigationMenuUpdateForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id']
        );
    }
}
