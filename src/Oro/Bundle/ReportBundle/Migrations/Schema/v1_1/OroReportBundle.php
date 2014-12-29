<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ReportBundle\Migrations\Schema\v1_0\OroReportBundle as OroReportSchemaMigration1_0;

class OroReportBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroReportSchemaMigration1_0::TABLE_NAME);
        $table->addColumn('chart_options', 'json_array', ['notnull' => false]);
    }
}
