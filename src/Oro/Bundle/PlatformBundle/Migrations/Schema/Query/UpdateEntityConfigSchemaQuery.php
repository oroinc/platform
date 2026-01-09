<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\Query;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigSchemaQuery extends ParametrizedMigrationQuery
{
    /** @var FieldConfigModel[] */
    private array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update json_array to json in oro_entity_config.data schema metadata');
        $this->doExecute($logger, true);
        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        if (empty($this->fields)) {
            return;
        }

        $fieldsByEntity = $this->groupFieldsByEntity();

        foreach ($fieldsByEntity as $entityId => $fieldNames) {
            $this->updateEntityConfig($entityId, $fieldNames, $logger, $dryRun);
        }
    }

    private function groupFieldsByEntity(): array
    {
        $fieldsByEntity = [];
        foreach ($this->fields as $field) {
            $entityId = $field->getEntity()->getId();
            $fieldsByEntity[$entityId][] = $field->getFieldName();
        }

        return $fieldsByEntity;
    }

    private function updateEntityConfig(
        int $entityId,
        array $fieldNames,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config WHERE id = :id';
        $this->logQuery($logger, $sql, ['id' => $entityId], ['id' => Types::INTEGER]);

        if ($dryRun) {
            return;
        }

        $config = $this->fetchEntityConfig($entityId);
        if (!$config) {
            return;
        }

        $data = $this->connection->convertToPHPValue($config['data'], Types::ARRAY);
        $updated = $this->updateSchemaMetadata($data, $fieldNames, $config['class_name'], $logger);

        if ($updated) {
            $this->saveEntityConfig($entityId, $data, $logger);
        }
    }

    private function fetchEntityConfig(int $entityId): ?array
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config WHERE id = :id';
        $config = $this->connection->fetchAssociative(
            $sql,
            ['id' => $entityId],
            ['id' => Types::INTEGER]
        );

        return $config ?: null;
    }

    private function updateSchemaMetadata(
        array &$data,
        array $fieldNames,
        string $className,
        LoggerInterface $logger
    ): bool {
        if (!isset($data['extend']['schema']['doctrine'])) {
            return false;
        }

        $updated = false;
        foreach ($data['extend']['schema']['doctrine'] as $exClass => &$classSchema) {
            if (isset($classSchema['fields'])) {
                $updated = $this->updateFieldSchemas(
                    $classSchema['fields'],
                    $fieldNames,
                    $className,
                    $logger
                ) || $updated;
            }
        }

        return $updated;
    }

    private function updateFieldSchemas(
        array &$fields,
        array $fieldNames,
        string $className,
        LoggerInterface $logger
    ): bool {
        $updated = false;

        foreach ($fields as $fieldName => &$fieldSchema) {
            if ($this->shouldUpdateFieldType($fieldSchema, $fieldNames, $fieldName)) {
                $fieldSchema['type'] = Types::JSON;
                $updated = true;

                $logger->info(sprintf(
                    'Updated schema metadata: %s::%s',
                    $className,
                    $fieldName
                ));
            }
        }

        return $updated;
    }

    private function shouldUpdateFieldType(array $fieldSchema, array $fieldNames, string $fieldName): bool
    {
        return in_array($fieldName, $fieldNames, true)
            && isset($fieldSchema['type'])
            && $fieldSchema['type'] === 'json_array';
    }

    private function saveEntityConfig(int $entityId, array $data, LoggerInterface $logger): void
    {
        $updateSql = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $entityId];
        $types = ['data' => Types::ARRAY, 'id' => Types::INTEGER];

        $this->logQuery($logger, $updateSql, $params, $types);
        $this->connection->executeStatement($updateSql, $params, $types);
    }
}
