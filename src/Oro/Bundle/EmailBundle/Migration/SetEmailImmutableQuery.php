<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Sets email.immutable = true for the specified fields of a given entity class,
 * skipping fields where the value is already true.
 *
 * This query does not check for user-made changes - email.immutable is a system-only setting
 * that prevents further modification through the UI.
 *
 * When $fieldNames is empty, the update applies to all fields of the entity.
 */
class SetEmailImmutableQuery extends ParametrizedMigrationQuery
{
    /**
     * @param string $entityClass The fully-qualified entity class name.
     * @param array<string> $fieldNames Field names to update; when empty, all fields are processed.
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly array $fieldNames = [],
    ) {
    }

    #[\Override]
    public function getDescription(): array
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
        $fieldConfigs = $this->loadFieldConfigs($logger);
        if (!$fieldConfigs) {
            return;
        }

        foreach ($fieldConfigs as $fieldName => $fieldConfig) {
            if ($this->shouldSkipField($fieldName, $fieldConfig)) {
                continue;
            }

            $this->updateFieldConfig($logger, $fieldConfig, $dryRun);
        }
    }

    private function shouldSkipField(string $fieldName, array $fieldConfig): bool
    {
        if ($this->fieldNames && !\in_array($fieldName, $this->fieldNames, true)) {
            return true;
        }

        if (($fieldConfig['data']['email']['immutable'] ?? false) === true) {
            // Skip because email.immutable is already set to true.
            return true;
        }

        return false;
    }

    private function updateFieldConfig(LoggerInterface $logger, array $fieldConfig, bool $dryRun): void
    {
        $data = $fieldConfig['data'];
        $data['email']['immutable'] = true;

        $sql = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $fieldConfig['id']];
        $types = ['data' => 'array', 'id' => 'integer'];

        $this->logQuery($logger, $sql, $params, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($sql, $params, $types);
        }
    }

    /**
     * Returns a map of field name to field config data for the entity class.
     *
     * @return array<string, array{id: int, data: array}>
     */
    private function loadFieldConfigs(LoggerInterface $logger): array
    {
        $sql = 'SELECT fc.id, fc.field_name, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $this->entityClass];
        $types = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);
        foreach ($rows as $row) {
            $result[$row['field_name']] = [
                'id' => $row['id'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array'),
            ];
        }

        return $result;
    }
}
