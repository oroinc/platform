<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_5;

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
        $this->createOroCalendarConnectionPropertyTable($schema);
        $this->addOroCalendarConnectionPropertyForeignKeys($schema);
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
        $table = $schema->getTable('oro_calendar_property');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            'FK_660946D19EB185F9'
        );
    }
}
