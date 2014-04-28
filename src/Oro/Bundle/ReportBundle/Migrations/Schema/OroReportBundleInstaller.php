<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ReportBundle\Migrations\Schema\v1_0\OroReportBundle;

class OroReportBundleInstaller implements Installation
{

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schemaMigration = new OroReportBundle();
        $schemaMigration->up($schema, $queries);
        $table = $schema->getTable(OroReportBundle::TABLE_NAME);
        $table->addColumn('chart_options', 'array');
    }
}
