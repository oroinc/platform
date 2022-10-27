<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Renames entity config field
 */
class RenameEntityConfigFieldQuery extends ParametrizedMigrationQuery
{
    private string $className;

    private string $oldFieldName;

    private string $newFieldName;

    public function __construct(string $className, string $oldFieldName, string $newFieldName)
    {
        $this->className = $className;
        $this->oldFieldName = $oldFieldName;
        $this->newFieldName = $newFieldName;
    }

    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    protected function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $entityConfigId = $this->connection
            ->executeQuery(
                'SELECT id FROM oro_entity_config WHERE class_name=:class_name',
                ['class_name' => $this->className],
                ['class_name' => 'string']
            )
            ->fetchFirstColumn();
        $entityConfigId = reset($entityConfigId);

        $entityConfigFieldId = $this->connection
            ->executeQuery(
                'SELECT id FROM oro_entity_config_field WHERE entity_id=:id AND field_name=:new_field_name',
                ['id' => $entityConfigId, 'new_field_name' => $this->newFieldName],
                ['id' => 'integer', 'new_field_name' => 'string']
            )
            ->fetchFirstColumn();

        if ($entityConfigFieldId) {
            // Skips update statement if the field with such name already exists.
            return;
        }

        $query = [
            'UPDATE oro_entity_config_field' .
            ' SET field_name=:new_field_name' .
            ' WHERE entity_id=:id AND field_name=:old_field_name',
            [
                'id' => $entityConfigId,
                'new_field_name' => $this->newFieldName,
                'old_field_name' => $this->oldFieldName,
            ],
            ['id' => 'integer', 'new_field_name' => 'string', 'old_field_name' => 'string'],
        ];

        $this->logQuery($logger, ...$query);
        if (!$dryRun) {
            $this->connection->executeStatement(...$query);
        }
    }
}
