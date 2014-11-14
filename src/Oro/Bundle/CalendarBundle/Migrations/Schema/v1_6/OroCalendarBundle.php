<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_property');
        $table->getColumn('background_color')->setOptions(['length' => 7]);
        $table->dropColumn('color');

        $this->updateBackgroundColorValues($queries);
    }

    /**
     * Updates backgroundColor fields to full hex format (e.g. '#FFFFFF')
     *
     * @param QueryBag $queries
     */
    protected function updateBackgroundColorValues(QueryBag $queries)
    {
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery('UPDATE oro_calendar_property
            SET background_color = concat(:prefix, background_color) WHERE background_color IS NOT NULL',
            ['prefix' => '#'])
        );
    }
}
