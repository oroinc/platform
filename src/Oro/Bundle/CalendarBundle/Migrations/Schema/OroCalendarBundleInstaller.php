<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_12';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCalendarTable($schema);
        $this->createOroSystemCalendarTable($schema);
        $this->createOroCalendarEventTable($schema);
        $this->createOroCalendarPropertyTable($schema);

        /** Foreign keys generation **/
        $this->addOroCalendarForeignKeys($schema);
        $this->addOroSystemCalendarForeignKeys($schema);
        $this->addOroCalendarEventForeignKeys($schema);
        $this->addOroCalendarPropertyForeignKeys($schema);
    }

    /**
     * Create oro_calendar table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addIndex(['organization_id'], 'idx_1d1715132c8a3de', []);
        $table->addIndex(['user_owner_id'], 'idx_1d171519eb185f9', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_system_calendar table
     *
     * @param Schema $schema
     */
    protected function createOroSystemCalendarTable(Schema $schema)
    {
        $table = $schema->createTable('oro_system_calendar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 7]);
        $table->addColumn('is_public', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn(
            'extend_description',
            'text',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'     => ['display' => true],
                    'dataaudit' => ['auditable' => true],
                    'form'      => ['type' => 'oro_resizeable_rich_text'],
                    'view'      => ['type' => 'html'],
                ]
            ]
        );
        $table->addIndex(['organization_id'], 'IDX_1DE3E2F032C8A3DE', []);
        $table->addIndex(['updated_at'], 'oro_system_calendar_up_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_calendar_event table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarEventTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('calendar_id', 'integer', ['notnull' => false]);
        $table->addColumn('system_calendar_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('start_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('end_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('all_day', 'boolean', []);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 7]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('invitation_status', 'string', ['default' => null, 'notnull' => false, 'length' => 32]);
        $table->addColumn('parent_id', 'integer', ['default' => null, 'notnull' => false]);
        $table->addIndex(['calendar_id', 'start_at', 'end_at'], 'oro_calendar_event_idx', []);
        $table->addIndex(['calendar_id'], 'idx_2ddc40dda40a2c8', []);
        $table->addIndex(['system_calendar_id', 'start_at', 'end_at'], 'oro_sys_calendar_event_idx', []);
        $table->addIndex(['system_calendar_id'], 'IDX_2DDC40DD55F0F9D0', []);
        $table->addIndex(['updated_at'], 'oro_calendar_event_up_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_calendar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCalendarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_system_calendar foreign keys
     *
     * @param Schema $schema
     */
    protected function addOroSystemCalendarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_system_calendar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_calendar_event foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCalendarEventForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar'),
            ['calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_system_calendar'),
            ['system_calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $table,
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_calendar_property table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarPropertyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_property');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('target_calendar_id', 'integer', []);
        $table->addColumn('calendar_alias', 'string', ['length' => 32]);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('position', 'integer', ['default' => 0]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 7]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['target_calendar_id'], 'IDX_660946D18D7AEDC2', []);
        $table->addUniqueIndex(['calendar_alias', 'calendar_id', 'target_calendar_id'], 'oro_calendar_prop_uq');
    }

    /**
     * Add oro_calendar_property foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCalendarPropertyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_property');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar'),
            ['target_calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
