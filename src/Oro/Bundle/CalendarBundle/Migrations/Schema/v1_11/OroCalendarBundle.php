<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
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
        $this->addForeignKeys($schema);
        $this->addEnums($schema);

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
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('calendar_event_id', 'integer', ['notnull' => true]);
        $table->addColumn('email', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('display_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['user_owner_id']);
        $table->addIndex(['calendar_event_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_calendar_event_attendee');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
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
            'origin',
            Attendee::ORIGIN_ENUM_CODE,
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

        $table->addColumn('related_attendee', 'integer', ['notnull' => false]);
        $table->addIndex(['related_attendee']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar_event_attendee'),
            ['related_attendee'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'origin',
            CalendarEvent::ORIGIN_ENUM_CODE,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'view'   => ['is_displayable' => false],
                'form'   => ['is_enabled' => false],
            ]
        );
    }
}
