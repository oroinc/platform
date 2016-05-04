<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SegmentBundle\Migrations\Schema\v1_5\UpdateDateVariablesQuery;

class OroReportBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateDateVariablesQuery('oro_report'));
    }
}
