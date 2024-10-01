<?php

namespace Oro\Bundle\EntityBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Migration that updates system option config for fallback entity field config
 */
class UpdateFallbackEntityFieldConfig extends ParametrizedMigrationQuery
{
    public function __construct(
        private string $entityName,
        private string $fieldName,
        private string $fallbackId,
        private string $fallbackOption
    ) {
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Update system option config for fallback entity field config';
    }

    /**
     * @throws \Exception|\Doctrine\DBAL\Driver\Exception
     */
    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $this->updateEntityConfig($logger);
    }

    /**
     * @throws \Exception|\Doctrine\DBAL\Driver\Exception
     */
    protected function updateEntityConfig(LoggerInterface $logger): void
    {
        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';
        $parameters = [$this->entityName, $this->fieldName];
        $row = $this->connection->fetchAssociative($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        if (isset($data['fallback']['fallbackList'])) {
            $themeConfigFallback = [$this->fallbackId => ['configName' => $this->fallbackOption]];
            $data['fallback']['fallbackList'] = array_merge($themeConfigFallback, $data['fallback']['fallbackList']);
        } else {
            $data['fallback']['fallbackList'][$this->fallbackId]['configName'] = $this->fallbackOption;
        }

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
