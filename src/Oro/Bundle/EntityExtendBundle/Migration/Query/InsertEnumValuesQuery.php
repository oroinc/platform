<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Inserts values for an enum into the database.
 */
class InsertEnumValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * @param ExtendExtension $extendExtension
     * @param string          $enumCode
     * @param EnumDataValue[] $values
     * @param bool            $allowUseHashForEnumTableName
     */
    public function __construct(
        private ExtendExtension $extendExtension,
        private string $enumCode,
        private array $values,
        private bool $allowUseHashForEnumTableName = false
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string|array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $sql = sprintf(
            'INSERT INTO %s (id, name, priority, is_default) VALUES (:id, :name, :priority, :is_default)',
            $this->extendExtension->getNameGenerator()->generateEnumTableName(
                $this->enumCode,
                $this->allowUseHashForEnumTableName
            )
        );
        $types = [
            'id'         => Types::STRING,
            'name'       => Types::STRING,
            'priority'   => Types::INTEGER,
            'is_default' => Types::BOOLEAN
        ];
        foreach ($this->values as $value) {
            $params = [
                'id'         => $value->getId(),
                'name'       => $value->getName(),
                'priority'   => $value->getPriority(),
                'is_default' => $value->isDefault()
            ];
            $this->logQuery($logger, $sql, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, $params, $types);
            }
        }
    }
}
