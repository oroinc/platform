<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCronBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_cron_schedule');
        $table->changeColumn('command', ['length' => 255]);
        $table->addColumn('args', 'array', []);
        $table->dropIndex('UQ_COMMAND');
        $table->addUniqueIndex(['command', 'args', 'definition'], 'UQ_COMMAND');
    }
}
