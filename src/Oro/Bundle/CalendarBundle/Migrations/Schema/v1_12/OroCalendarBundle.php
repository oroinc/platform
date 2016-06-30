<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension $extendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createAttendee($schema);
        $this->createRecurrenceTable($schema);

        $this->addEnums($schema);
        $this->addForeignKeys($schema);
        $this->updateCalendarEvent($schema);

        $queries->addQuery(new ConvertCalendarEventOwnerToAttendee());
    }

    /**
     * @param Schema $schema
     */
    protected function createAttendee(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_event_attendee');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('calendar_event_id', 'integer', ['notnull' => true]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('display_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['user_id']);
        $table->addIndex(['calendar_event_id']);
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
        $table->addColumn('timezone', 'string', ['notnull' => true, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['start_time'], 'oro_calendar_r_start_time_idx', []);
        $table->addIndex(['end_time'], 'oro_calendar_r_end_time_idx', []);
        $table->addIndex(['calculated_end_time'], 'oro_calendar_r_c_end_time_idx', []);
    }

    /**
     * @param Schema $schema
     */
    protected function addForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event_attendee');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar_event'),
            ['calendar_event_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addEnums(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event_attendee');

        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'status',
            Attendee::STATUS_ENUM_CODE,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );

        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'type',
            Attendee::TYPE_ENUM_CODE,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function updateCalendarEvent(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event');

        $table->addColumn('related_attendee_id', 'integer', ['notnull' => false]);
        $table->addColumn('original_start_at', 'datetime', ['notnull' => false]);
        $table->addColumn('recurring_event_id', 'integer', ['notnull' => false]);
        $table->addColumn('recurrence_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_cancelled', 'boolean', ['default' => false]);

        $table->addIndex(['related_attendee_id']);
        $table->addIndex(['original_start_at'], 'oro_calendar_event_osa_idx');

        $table->addUniqueIndex(['recurrence_id'], 'UNIQ_2DDC40DD2C414CE8');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar_event_attendee'),
            ['related_attendee_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar_recurrence'),
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
