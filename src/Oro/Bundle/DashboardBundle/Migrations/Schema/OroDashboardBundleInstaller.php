<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroDashboardBundleInstaller implements Installation
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
        $this->createOroDashboardTable($schema);
        $this->createOroDashboardActiveTable($schema);
        $this->createOroDashboardWidgetTable($schema);
        $this->createOroDashboardWidgetStateTable($schema);

        /** Foreign keys generation **/
        $this->addOroDashboardForeignKeys($schema);
        $this->addOroDashboardActiveForeignKeys($schema);
        $this->addOroDashboardWidgetForeignKeys($schema);
        $this->addOroDashboardWidgetStateForeignKeys($schema);
    }

    /**
     * Create oro_dashboard table
     *
     * @param Schema $schema
     */
    protected function createOroDashboardTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dashboard');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_default', 'boolean', ['default' => '0']);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_DF2802EF9EB185F9', []);
        $table->addIndex(['is_default'], 'dashboard_is_default_idx', []);
        $table->addIndex(['organization_id'], 'IDX_DF2802EF32C8A3DE', []);
    }

    /**
     * Create oro_dashboard_active table
     *
     * @param Schema $schema
     */
    protected function createOroDashboardActiveTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dashboard_active');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_858BA17EA76ED395', []);
        $table->addIndex(['dashboard_id'], 'IDX_858BA17EB9D04D2B', []);
        $table->addIndex(['organization_id'], 'IDX_858BA17E32C8A3DE', []);
    }

    /**
     * Create oro_dashboard_widget table
     *
     * @param Schema $schema
     */
    protected function createOroDashboardWidgetTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dashboard_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('layout_position', 'simple_array', ['comment' => '(DC2Type:simple_array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['dashboard_id'], 'IDX_4B6C43ACB9D04D2B', []);
    }

    /**
     * Create oro_dashboard_widget_state table
     *
     * @param Schema $schema
     */
    protected function createOroDashboardWidgetStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dashboard_widget_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('widget_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_expanded', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['widget_id'], 'IDX_4B4F5F87FBE885E2', []);
        $table->addIndex(['user_owner_id'], 'IDX_4B4F5F879EB185F9', []);
    }

    /**
     * Add oro_dashboard foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDashboardForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dashboard_active foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDashboardActiveForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dashboard_widget foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDashboardWidgetForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dashboard_widget_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDashboardWidgetStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_widget_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard_widget'),
            ['widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
