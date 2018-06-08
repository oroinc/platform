<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCronBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v2_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCronScheduleTable($schema);
    }

    /**
     * Create oro_cron_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroCronScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cron_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('command', 'string', ['length' => 255]);
        $table->addColumn('args', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('args_hash', 'string', ['length' => 32]);
        $table->addColumn('definition', 'string', ['notnull' => false, 'length' => 100]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['command', 'args_hash', 'definition'], 'UQ_COMMAND');
    }
}
