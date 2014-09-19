<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroCalendarBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
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

        /** Foreign keys generation **/
        $this->addOroCalendarForeignKeys($schema);
        $this->addOroCalendarEventForeignKeys($schema);
        $this->addOroCalendarConnectionForeignKeys($schema);
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
        $table->addColumn('title', 'text', []);
        $table->addColumn('start_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('end_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('all_day', 'boolean', []);
        $table->addIndex(['calendar_id', 'start_at', 'end_at'], 'oro_calendar_event_idx', []);
        $table->addIndex(['calendar_id'], 'idx_2ddc40dda40a2c8', []);
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
}
