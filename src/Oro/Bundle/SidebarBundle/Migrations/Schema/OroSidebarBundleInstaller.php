<?php

namespace Oro\Bundle\SidebarBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSidebarBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroSidebarStateTable($schema);
        $this->createOroSidebarWidgetTable($schema);

        /** Foreign keys generation **/
        $this->addOroSidebarStateForeignKeys($schema);
        $this->addOroSidebarWidgetForeignKeys($schema);
    }

    /**
     * Create oro_sidebar_state table
     *
     * @param Schema $schema
     */
    protected function createOroSidebarStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_sidebar_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['user_id', 'position'], 'sidebar_state_unique_idx');
        $table->addIndex(['user_id'], 'IDX_AB2BC195A76ED395', []);
    }

    /**
     * Create oro_sidebar_widget table
     *
     * @param Schema $schema
     */
    protected function createOroSidebarWidgetTable(Schema $schema)
    {
        $table = $schema->createTable('oro_sidebar_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('state', 'string', ['length' => 22]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_2FFBEA9CA76ED395', []);
        $table->addIndex(['user_id', 'placement'], 'sidebar_widgets_user_placement_idx', []);
        $table->addIndex(['position'], 'sidebar_widgets_position_idx', []);
        $table->addIndex(['organization_id'], 'IDX_2FFBEA9C32C8A3DE', []);
    }

    /**
     * Add oro_sidebar_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSidebarStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_sidebar_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_sidebar_widget foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSidebarWidgetForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_sidebar_widget');
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
}
