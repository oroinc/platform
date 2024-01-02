<?php

namespace Oro\Bundle\LoggerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * OroLoggerBundle installer
 */
class OroLoggerBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroLoggerLogEntryTable($schema);
    }

    /**
     * Create oro_logger_log_entry table
     */
    private function createOroLoggerLogEntryTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_logger_log_entry');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('message', 'text');
        $table->addColumn('context', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('level', 'smallint');
        $table->addColumn('channel', 'string', ['length' => 255]);
        $table->addColumn('datetime', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('extra', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->setPrimaryKey(['id']);
    }
}
