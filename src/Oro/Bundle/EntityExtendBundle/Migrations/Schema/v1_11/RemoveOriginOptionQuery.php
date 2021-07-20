<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_11;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes "origin" option from entity and field configs.
 */
class RemoveOriginOptionQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param false           $dryRun
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $this->removeOriginOption($logger, 'oro_entity_config', $dryRun);
        $this->removeOriginOption($logger, 'oro_entity_config_field', $dryRun);
    }

    private function removeOriginOption(LoggerInterface $logger, string $tableName, bool $dryRun): void
    {
        $configs = $this->loadConfigs($logger, $tableName);
        foreach ($configs as [$id, $data]) {
            if (array_key_exists('extend', $data) && array_key_exists('origin', $data['extend'])) {
                unset($data['extend']['origin']);
                $this->saveConfig($logger, $tableName, $id, $data, $dryRun);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $tableName
     *
     * @return array [[id, data], ...]
     */
    private function loadConfigs(LoggerInterface $logger, string $tableName): array
    {
        $query = sprintf('SELECT id, data FROM %s', $tableName);
        $this->logQuery($logger, $query);

        $result = [];
        $rows = $this->connection->fetchAll($query);
        foreach ($rows as $row) {
            $result[] = [
                $row['id'],
                $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }

    private function saveConfig(LoggerInterface $logger, string $tableName, int $id, array $data, bool $dryRun): void
    {
        $query = sprintf('UPDATE %s SET data = :data WHERE id = :id', $tableName);
        $params = ['data' => $data, 'id' => $id];
        $types = ['data' => 'array', 'id' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }
}
