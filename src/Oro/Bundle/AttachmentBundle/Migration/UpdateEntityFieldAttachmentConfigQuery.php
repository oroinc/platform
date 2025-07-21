<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Query should be used when entity field attachment config should be changed
 */
class UpdateEntityFieldAttachmentConfigQuery extends ParametrizedMigrationQuery
{
    public function __construct(
        protected string $className,
        protected string $fieldName,
        protected array $attachmentData
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

    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $classConfig = $this->loadEntityConfigData($logger);
        if ($classConfig) {
            $data = $this->connection->convertToPHPValue($classConfig['data'], 'array');

            $data = $this->getNewData($data);

            $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $classConfig['id']];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    private function loadEntityConfigData(LoggerInterface $logger): bool|array
    {
        $sql = <<<SQL
            SELECT ecf.id, ecf.data
            FROM oro_entity_config_field ecf
            WHERE ecf.entity_id IN (
                SELECT id
                FROM oro_entity_config
                WHERE class_name = :class
                LIMIT 1
            )
            AND ecf.field_name = :field
        SQL;

        $params = ['class' => $this->className, 'field' => $this->fieldName];
        $types  = ['class' => 'string', 'field' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        return $rows[0] ?? false;
    }

    private function getNewData(array $data): array
    {
        $data['attachment'] = (isset($data['attachment'])) ?
            array_merge($data['attachment'], $this->attachmentData) :
            $this->attachmentData;

        return $data;
    }
}
