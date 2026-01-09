<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Exception\DataFormatException;
use Psr\Log\LoggerInterface;

/**
 * Changes column type to JSON and migrates data.
 */
class MigrateColumnToJsonQuery implements MigrationQuery, ConnectionAwareInterface
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
        $column = $this->schema->getTable($this->tableName)->getColumn($this->columnName);
        if ($column->getType()->getName() === Types::JSON) {
            $logger->warning(sprintf('Column "%s" is already of type "%s", skipping.', $this->columnName, Types::JSON));

            return;
        }

        $this->changeColumnType($this->tableName, $this->columnName, $logger, $dryRun);
        $this->migrateToJsonb($this->tableName, $this->columnName, $logger, $dryRun);
    }

    protected function changeColumnType(
        string $tableName,
        string $columnName,
        LoggerInterface $logger,
        bool $dryRun = false
    ) {
        $alterQuery = sprintf(
            'ALTER TABLE %1$s ALTER COLUMN %2$s TYPE JSONB USING (\'"\' || %2$s || \'"\')::jsonb',
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

    protected function migrateToJsonb(
        string $tableName,
        string $columnName,
        LoggerInterface $logger,
        bool $dryRun = false
    ): void {
        $primaryKey = $this->getPrimaryKeyColumn($this->tableName);

        // Iterate over data, decode it with PHP because string is it may be stored with modifications like base64
        // Then put it back as JSON
        $select = $this->connection->createQueryBuilder()
            ->from($tableName)
            ->select($primaryKey, $columnName);

        $update = $this->connection->createQueryBuilder();
        $update->update($tableName)
            ->set($columnName, ':value')
            ->where($update->expr()->eq($primaryKey, ':pkValue'));

        $currentType = $this->schema->getTable($tableName)->getColumn($columnName)->getType();
        foreach ($this->connection->iterateKeyValue($select->getSQL()) as $pkValue => $value) {
            $data = $currentType->convertToPHPValue($value, $this->connection->getDatabasePlatform());
            try {
                $this->assertScalarData($data);
            } catch (DataFormatException $e) {
                throw new \RuntimeException(
                    message: $e->getMessage()
                        . sprintf('Table %s, column %s, PK %s', $tableName, $columnName, $pkValue),
                    previous: $e
                );
            }

            $update
                ->setParameter('pkValue', $pkValue)
                ->setParameter('value', $data, Types::JSON);

            $logger->info($update->getSQL(), ['parameters' => $update->getParameters()]);
            if (!$dryRun) {
                $update->execute();
            }
        }
    }

    protected function getPrimaryKeyColumn(string $tableName): ?string
    {
        $indexes = $this->connection->createSchemaManager()?->listTableIndexes($tableName);

        if (!empty($indexes['primary'])) {
            $columns = $indexes['primary']->getColumns();

            return reset($columns);
        }

        return null;
    }

    private function assertScalarData($data): void
    {
        if ($data === null) {
            return;
        }

        if (\is_object($data) || \is_resource($data)) {
            throw new DataFormatException('Only array of scalars are supported');
        }

        if (\is_array($data)) {
            foreach ($data as $value) {
                $this->assertScalarData($value);
            }
        }
    }
}
