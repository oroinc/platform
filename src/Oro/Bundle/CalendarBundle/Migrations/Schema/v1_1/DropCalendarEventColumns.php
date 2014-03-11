<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropCalendarEventColumns implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event');

        $table->dropColumn('reminder');
        $table->dropColumn('remind_at');
        $table->dropColumn('reminded');
    }
}
