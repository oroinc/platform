<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schemas\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroCalendarBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_calendar **/
        $table = $schema->createTable('oro_calendar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_1D171519EB185F9', []);
        /** End of generate table oro_calendar **/

        /** Generate table oro_calendar_connection **/
        $table = $schema->createTable('oro_calendar_connection');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('connected_calendar_id', 'integer', []);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('color', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 6]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['calendar_id', 'connected_calendar_id'], 'oro_calendar_connection_uq');
        $table->addIndex(['calendar_id'], 'IDX_25D13AB8A40A2C8', []);
        $table->addIndex(['connected_calendar_id'], 'IDX_25D13AB8F94143E3', []);
        /** End of generate table oro_calendar_connection **/

        /** Generate table oro_calendar_event **/
        $table = $schema->createTable('oro_calendar_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('title', 'text', []);
        $table->addColumn('start_at', 'datetime', []);
        $table->addColumn('end_at', 'datetime', []);
        $table->addColumn('all_day', 'boolean', []);
        $table->addColumn('reminder', 'boolean', []);
        $table->addColumn('remind_at', 'datetime', ['notnull' => false]);
        $table->addColumn('reminded', 'boolean', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['calendar_id'], 'IDX_2DDC40DDA40A2C8', []);
        $table->addIndex(['calendar_id', 'start_at', 'end_at'], 'oro_calendar_event_idx', []);
        /** End of generate table oro_calendar_event **/

        /** Generate foreign keys for table oro_calendar **/
        $table = $schema->getTable('oro_calendar');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_calendar **/

        /** Generate foreign keys for table oro_calendar_connection **/
        $table = $schema->getTable('oro_calendar_connection');
        $table->addForeignKeyConstraint($schema->getTable('oro_calendar'), ['connected_calendar_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_calendar'), ['calendar_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_calendar_connection **/

        /** Generate foreign keys for table oro_calendar_event **/
        $table = $schema->getTable('oro_calendar_event');
        $table->addForeignKeyConstraint($schema->getTable('oro_calendar'), ['calendar_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_calendar_event **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
