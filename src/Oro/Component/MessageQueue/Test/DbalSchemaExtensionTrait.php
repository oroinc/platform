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
    public function messageQueueEnsureTableExists($tableName)
    {
        $connection = $this->messageQueueCreateConnection($tableName);
        $dbalConnection = $connection->getDBALConnection();
        $schemaManager = $dbalConnection->getSchemaManager();

        try {
            $schemaManager->dropTable($tableName);
        } catch (DriverException $e) {
        }

        $schema = new DbalSchema($dbalConnection, $tableName);
        foreach ($schema->getTables() as $table) {
            $schemaManager->createTable($table);
        }
    }

    /**
     * @param string $tableName
     *
     * @return DbalConnection
     */
    public function messageQueueCreateConnection($tableName)
    {
        $dbal = $this->getContainer()->get('doctrine.dbal.default_connection');

        return new DbalConnection($dbal, $tableName);
    }
}
