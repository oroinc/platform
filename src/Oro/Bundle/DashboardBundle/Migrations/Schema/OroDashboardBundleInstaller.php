<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroDashboardBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    private ExtendExtension $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * {@inheritDoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroDashboardActiveTable($schema);
        $this->createOroDashboardTable($schema);
        $this->createOroDashboardWidgetTable($schema);
        $this->createOroDashboardWidgetStateTable($schema);

        /** Foreign keys generation **/
        $this->addOroDashboardActiveForeignKeys($schema);
        $this->addOroDashboardForeignKeys($schema);
        $this->addOroDashboardWidgetForeignKeys($schema);
        $this->addOroDashboardWidgetStateForeignKeys($schema);

        $this->addDashboardTypeEnumField($schema, $queries);
    }

    /**
     * Create oro_dashboard_active table
     */
    protected function createOroDashboardActiveTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_dashboard_active');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'idx_858ba17e32c8a3de', []);
        $table->addIndex(['dashboard_id'], 'idx_858ba17eb9d04d2b', []);
        $table->addIndex(['user_id'], 'idx_858ba17ea76ed395', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_dashboard table
     */
    protected function createOroDashboardTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_dashboard');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updatedat', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addIndex(['is_default'], 'dashboard_is_default_idx', []);
        $table->addIndex(['user_owner_id'], 'idx_df2802ef9eb185f9', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_df2802ef32c8a3de', []);
    }

    /**
     * Create oro_dashboard_widget table
     */
    protected function createOroDashboardWidgetTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_dashboard_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('layout_position', 'simple_array', ['comment' => '(DC2Type:simple_array)']);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)', 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['dashboard_id'], 'idx_4b6c43acb9d04d2b', []);
    }

    /**
     * Create oro_dashboard_widget_state table
     */
    protected function createOroDashboardWidgetStateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_dashboard_widget_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('widget_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_expanded', 'boolean', []);
        $table->addIndex(['user_owner_id'], 'idx_4b4f5f879eb185f9', []);
        $table->addIndex(['widget_id'], 'idx_4b4f5f87fbe885e2', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_dashboard_active foreign keys.
     */
    protected function addOroDashboardActiveForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard_active');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_dashboard foreign keys.
     */
    protected function addOroDashboardForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_dashboard_widget foreign keys.
     */
    protected function addOroDashboardWidgetForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_dashboard_widget_state foreign keys.
     */
    protected function addOroDashboardWidgetStateForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard_widget_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard_widget'),
            ['widget_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add dashboard_type enum field to the oro_dashboard table and adds widgets default dashboard type.
     */
    protected function addDashboardTypeEnumField(Schema $schema, QueryBag $queries): void
    {
        $enumTable = $this->extendExtension->addEnumField(
            $schema,
            $schema->getTable('oro_dashboard'),
            'dashboard_type',
            'dashboard_type',
            false,
            false,
            [
                'extend'    => ['owner' => ExtendScope::OWNER_SYSTEM],
                'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE, 'show_filter' => false],
                'form'      => ['is_enabled' => false],
                'view'      => ['is_displayable' => false],
                'merge'     => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        );

        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', ['widgets']);
        $enumTable->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            sprintf(
                'INSERT INTO %s (id, name, priority, is_default) VALUES (:id, :name, :priority, :is_default)',
                $enumTable->getName()
            ),
            [
                'id' => 'widgets',
                'name' => 'Widgets',
                'priority' => 1,
                'is_default' => true
            ],
            [
                'id' => Types::STRING,
                'name' => Types::STRING,
                'priority' => Types::INTEGER,
                'is_default' => Types::BOOLEAN
            ]
        ));
    }
}
