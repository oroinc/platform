<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_calendar_property');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('target_calendar_id', 'integer', []);
        $table->addColumn('calendar_alias', 'string', ['length' => 32]);
        $table->addColumn('calendar_id', 'integer', []);
        $table->addColumn('position', 'integer', ['default' => 0]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->addColumn('color', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 6]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['target_calendar_id'], 'IDX_660946D18D7AEDC2', []);
        $table->addUniqueIndex(['calendar_alias', 'calendar_id', 'target_calendar_id'], 'oro_calendar_prop_uq');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_calendar'),
            ['target_calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        // fill oro_calendar_property from oro_calendar_connection
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'INSERT INTO oro_calendar_property'
                . ' (target_calendar_id, calendar_alias, calendar_id, color, background_color)'
                . ' SELECT calendar_id, :calendar_alias, connected_calendar_id, color, background_color'
                . ' FROM oro_calendar_connection',
                ['calendar_alias' => 'user'],
                ['calendar_alias' => 'string']
            )
        );
    }
}
