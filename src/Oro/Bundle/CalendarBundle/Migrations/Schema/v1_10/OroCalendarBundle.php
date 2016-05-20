<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createRecurrenceTable($schema);
        $this->updateCalendarEventsTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createRecurrenceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_recurrence');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('recurrence_type', 'string', ['notnull' => true, 'length' => 16]);
        $table->addColumn('interval', 'integer', []);
        $table->addColumn('instance', 'integer', ['notnull' => false]);
        $table->addColumn('day_of_week', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('day_of_month', 'integer', ['notnull' => false]);
        $table->addColumn('month_of_year', 'integer', ['notnull' => false]);
        $table->addColumn('start_time', 'datetime', []);
        $table->addColumn('end_time', 'datetime', ['notnull' => false]);
        $table->addColumn('calculated_end_time', 'datetime', []);
        $table->addColumn('occurrences', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['start_time'], 'oro_calendar_r_start_time_idx', []);
        $table->addIndex(['end_time'], 'oro_calendar_r_end_time_idx', []);
        $table->addIndex(['calculated_end_time'], 'oro_calendar_r_c_end_time_idx', []);
    }

    /**
     * @param Schema $schema
     */
    protected function updateCalendarEventsTable(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event');
        $table->addColumn('original_start_at', 'datetime', ['notnull' => false]);
        $table->addColumn('recurring_event_id', 'integer', ['notnull' => false]);
        $table->addColumn('recurrence_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_cancelled', 'boolean', ['default' => false]);
        $table->addIndex(['original_start_at'], 'oro_calendar_event_osa_idx');
        $table->addUniqueIndex(['recurrence_id'], 'UNIQ_2DDC40DD2C414CE8');
        $table->addForeignKeyConstraint(
            $table,
            ['recurrence_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $table,
            ['recurring_event_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
