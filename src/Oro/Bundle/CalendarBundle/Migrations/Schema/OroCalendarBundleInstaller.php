<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCalendarTable($schema);
        $this->createOroCalendarEventTable($schema);
        $this->createOroCalendarConnectionTable($schema);
        $this->createOroCalendarConnectionPropertyTable($schema);

        /** Foreign keys generation **/
        $this->addOroCalendarForeignKeys($schema);
        $this->addOroCalendarEventForeignKeys($schema);
        $this->addOroCalendarConnectionForeignKeys($schema);
        $this->addOroCalendarConnectionPropertyForeignKeys($schema);
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
     * Create oro_calendar_event table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarEventTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('start_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('end_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('all_day', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addIndex(['calendar_id', 'start_at', 'end_at'], 'oro_calendar_event_idx', []);
        $table->addIndex(['calendar_id'], 'idx_2ddc40dda40a2c8', []);
        $table->addIndex(['updated_at'], 'oro_calendar_event_updated_at_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_calendar_connection table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarConnectionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_connection');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('connected_calendar_id', 'integer', []);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('color', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 6]);
        $table->addUniqueIndex(['calendar_id', 'connected_calendar_id'], 'oro_calendar_connection_uq');
        $table->addIndex(['calendar_id'], 'idx_25d13ab8a40a2c8', []);
        $table->addIndex(['connected_calendar_id'], 'idx_25d13ab8f94143e3', []);
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
    }

    /**
     * Add oro_calendar_connection foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCalendarConnectionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_connection');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar'),
            ['calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar'),
            ['connected_calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_calendar_property table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarConnectionPropertyTable(Schema $schema)
    {
        /** Generate table oro_calendar **/
        $table = $schema->createTable('oro_calendar_property');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('calendar_uid', 'string', ['notnull' => true, 'length' => 32]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_660946D19EB185F9', []);
        $table->addUniqueIndex(['calendar_uid', 'user_owner_id'], 'oro_calendar_property_uq');
        /** End of generate table oro_calendar **/
    }

    /**
     * Add oro_calendar_property foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCalendarConnectionPropertyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'FK_660946D19EB185F9'
        );
    }
}
