<?php

namespace Oro\Bundle\ActivityBundle\Migrations\Schema;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateActivityButtonConfigQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->migrateConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function migrateConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $id => $data) {
            if (!isset($data['activity']['action_widget'])) {
                continue;
            }
            $data['activity']['action_button_widget'] = $data['activity']['action_widget'];
            unset($data['activity']['action_widget']);

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $id];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array key = {config id}, value = data
     */
    protected function loadConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAllAssociative($sql);
        foreach ($rows as $row) {
            $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
