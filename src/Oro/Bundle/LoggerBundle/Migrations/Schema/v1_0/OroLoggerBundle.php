<?php

namespace Oro\Bundle\LoggerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * OroLoggerBundle initial migration
 */
class OroLoggerBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroLoggerLogEntryTable($schema);
    }

    /**
     * Create oro_logger_log_entry table
     */
    protected function createOroLoggerLogEntryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_logger_log_entry');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('message', 'text');
        $table->addColumn('context', 'json', ['comment' => '(DC2Type:json)']);
        $table->addColumn('level', 'smallint');
        $table->addColumn('channel', 'string', ['length' => 255]);
        $table->addColumn('datetime', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('extra', 'json', ['comment' => '(DC2Type:json)']);
        $table->setPrimaryKey(['id']);
    }
}
