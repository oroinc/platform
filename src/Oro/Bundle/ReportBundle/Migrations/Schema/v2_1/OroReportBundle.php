<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroReportBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCalendarDateTable($schema);
    }

    /**
     * Create oro_order_shipping_tracking table
     *
     * @param Schema $schema
     */
    protected function createOroCalendarDateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_calendar_date');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('date', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }
}
