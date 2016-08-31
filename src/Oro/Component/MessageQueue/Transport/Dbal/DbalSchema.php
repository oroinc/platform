<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Schema\Schema as BaseSchema;
use Doctrine\DBAL\Connection;

class DbalSchema extends BaseSchema
{
    /**
     * @var string
     */
    private $queueTableName;

    /**
     * @param Connection $connection
     * @param string     $queueTableName
     */
    public function __construct(Connection $connection, $queueTableName)
    {
        $this->queueTableName = $queueTableName;

        $schemaConfig = $connection->getSchemaManager()->createSchemaConfig();

        parent::__construct([], [], $schemaConfig);

        $this->addQueueTable();
    }

    /**
     * Merges ACL schema with the given schema.
     *
     * @param BaseSchema $schema
     */
    public function addToSchema(BaseSchema $schema)
    {
        foreach ($this->getTables() as $table) {
            $schema->_addTable($table);
        }

        foreach ($this->getSequences() as $sequence) {
            $schema->_addSequence($sequence);
        }
    }

    private function addQueueTable()
    {
        $table = $this->createTable($this->queueTableName);
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true,]);
        $table->addColumn('body', 'text', ['notnull' => false,]);
        $table->addColumn('headers', 'text', ['notnull' => false,]);
        $table->addColumn('properties', 'text', ['notnull' => false,]);
        $table->addColumn('consumer_id', 'string', ['notnull' => false,]);
        $table->addColumn('redelivered', 'boolean', ['notnull' => false,]);
        $table->addColumn('queue', 'string');
        $table->addColumn('priority', 'smallint');
        $table->addColumn('delivered_at', 'integer', ['notnull' => false,]);
        $table->addColumn('delayed_until', 'integer', ['notnull' => false,]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['consumer_id']);
        $table->addIndex(['queue']);
        $table->addIndex(['priority']);
        $table->addIndex(['delivered_at']);
        $table->addIndex(['delayed_until']);
    }
}
