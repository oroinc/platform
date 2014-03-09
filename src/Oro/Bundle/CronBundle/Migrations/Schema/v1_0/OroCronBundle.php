<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCronBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_cron_schedule **/
        $table = $schema->createTable('oro_cron_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('command', 'string', ['length' => 50]);
        $table->addColumn('definition', 'string', ['notnull' => false, 'length' => 100]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['command'], 'UQ_COMMAND');
        /** End of generate table oro_cron_schedule **/
    }
}
