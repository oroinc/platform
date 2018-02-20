<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroNavigationMenuUpdateTable($schema);
        $this->createOroNavigationMenuUpdateTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroNavigationMenuUpdateTitleForeignKeys($schema);
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
        $table->addColumn('uri', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'integer');
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
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
}
