<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Remove outdated entity configuration data with scope name "note".
 */
class RemoveNoteConfigurationScopeQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription(): array|string
    {
        $logger = new ArrayLogger();
        $logger->info('Remove outdated entity configuration data with scope name "note".');
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
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);
        $rows = $this->connection->fetchAllAssociative($sql);
        foreach ($rows as $row) {
            $data = !empty($row['data'])
                ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY)
                : [];
            if (!\array_key_exists('note', $data)) {
                continue;
            }

            unset($data['note']);
            $sql = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $row['id']];
            $types = ['data' => Types::ARRAY, 'id' => Types::INTEGER];
            $this->logQuery($logger, $sql, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, $params, $types);
            }
        }
    }
}
