<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveDaemonMonitorSchedule implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery("DELETE FROM oro_cron_schedule WHERE command = 'oro:cron:daemon'");
    }
}
