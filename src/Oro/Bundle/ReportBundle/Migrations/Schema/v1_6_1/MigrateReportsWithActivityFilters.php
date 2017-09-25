<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityListBundle\Migration\MigrateActivityListFilterQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateReportsWithActivityFilters implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new MigrateActivityListFilterQuery('oro_report'));
    }
}
