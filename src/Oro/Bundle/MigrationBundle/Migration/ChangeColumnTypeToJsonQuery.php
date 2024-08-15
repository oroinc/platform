<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

/**
 * Changes column type to JSON
 */
class ChangeColumnTypeToJsonQuery implements MigrationQuery, ConnectionAwareInterface
{
    protected ?Connection $connection = null;

    public function __construct(
        private string $tableName,
        private string $columnName,
        private Schema $schema
    ) {
    }

    #[\Override]
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger, false);
    }

    protected function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->changeColumnType($this->tableName, $this->columnName, $logger, $dryRun);
    }

    protected function changeColumnType(
        string $tableName,
        string $columnName,
        LoggerInterface $logger,
        bool $dryRun = false
    ) {
        $column = $this->schema->getTable($this->tableName)->getColumn($this->columnName);
        if ($column->getType()->getName() === Types::JSON) {
            $logger->warning(sprintf('Column "%s" is already of type "%s", skipping.', $this->columnName, Types::JSON));

            return;
        }

        $alterQuery = sprintf(
            'ALTER TABLE %1$s ALTER COLUMN %2$s TYPE JSONB USING %2$s::jsonb',
            $tableName,
            $columnName
        );
        $commentQuery = sprintf(
            "COMMENT ON COLUMN %s.%s IS '(DC2Type:json)'",
            $tableName,
            $columnName
        );

        $logger->info($alterQuery);
        $logger->info($commentQuery);

        if (!$dryRun) {
            $this->connection->executeQuery($alterQuery);
            $this->connection->executeQuery($commentQuery);
        }
    }
}
