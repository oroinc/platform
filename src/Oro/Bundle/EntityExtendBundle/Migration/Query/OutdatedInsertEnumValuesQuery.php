<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Inserts values for an enum into the database.
 */
class OutdatedInsertEnumValuesQuery extends ParametrizedMigrationQuery
{
    public function __construct(
        private readonly OutdatedExtendExtension $extendExtension,
        private readonly string $enumCode,
        private readonly array $values,
        private readonly bool $allowUseHashForEnumTableName = false
    ) {
    }

    #[\Override]
    public function getDescription(): string|array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $sql = sprintf(
            'INSERT INTO %s (id, name, priority, is_default) VALUES (:id, :name, :priority, :is_default)',
            $this->extendExtension::generateEnumTableName(
                $this->enumCode,
                $this->allowUseHashForEnumTableName
            )
        );
        $types = [
            'id' => Types::STRING,
            'name' => Types::STRING,
            'priority' => Types::INTEGER,
            'is_default' => Types::BOOLEAN
        ];
        foreach ($this->values as $value) {
            $params = [
                'id' => $value->getId(),
                'name' => $value->getName(),
                'priority' => $value->getPriority(),
                'is_default' => $value->isDefault()
            ];
            $this->logQuery($logger, $sql, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, $params, $types);
            }
        }
    }
}
