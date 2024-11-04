<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v2_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroReportBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_calendar_date')) {
            $queries->addPostQuery(new ConvertDateColumnFromDateTimeToDateQuery());
        }
    }
}
