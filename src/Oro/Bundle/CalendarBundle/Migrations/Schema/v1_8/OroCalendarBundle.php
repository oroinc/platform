<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_8;

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
        $table = $schema->getTable('oro_calendar_event');
        $table->addColumn('invitation_status', 'string', ['default' => null, 'notnull' => false, 'length' => 32]);
        $table->addColumn('parent_id', 'integer', ['default' => null, 'notnull' => false]);
        $table->addForeignKeyConstraint(
            $table,
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
