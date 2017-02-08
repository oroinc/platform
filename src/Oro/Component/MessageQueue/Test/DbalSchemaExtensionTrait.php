<?php
namespace Oro\Component\MessageQueue\Test;

use Doctrine\DBAL\Exception\DriverException;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;

trait DbalSchemaExtensionTrait
{
    /**
     * @param string $tableName
     */
    public function ensureTableExists($tableName)
    {
        $connection = $this->createConnection($tableName);
        $dbalConnection = $connection->getDBALConnection();
        $schemaManager = $dbalConnection->getSchemaManager();

        $schema = new DbalSchema($dbalConnection, $tableName);
        foreach ($schema->getTables() as $table) {
            $schemaManager->createTable($table);
        }
    }

    /**
     * @param string $tableName
     */
    public function dropTable($tableName)
    {
        $connection = $this->createConnection($tableName);
        $dbalConnection = $connection->getDBALConnection();
        $schemaManager = $dbalConnection->getSchemaManager();

        $schemaManager->dropTable($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return DbalConnection
     */
    public function createConnection($tableName)
    {
        $dbal = $this->getContainer()->get('doctrine.dbal.default_connection');

        return new DbalConnection($dbal, $tableName);
    }
}
