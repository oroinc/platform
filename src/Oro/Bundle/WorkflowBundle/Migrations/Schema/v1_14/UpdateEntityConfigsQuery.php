<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;

class UpdateEntityConfigsQuery extends ParametrizedMigrationQuery
{
    const CONFIG_KEY = 'workflow';

    const OLD_CONFIG_KEY = 'active_workflow';
    const NEW_CONFIG_KEY = WorkflowSystemConfigManager::CONFIG_KEY;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
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
        $queries = [];

        // prepare update queries
        $rows = $this->getRows($logger);
        foreach ($rows as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            if (array_key_exists(self::CONFIG_KEY, $data) &&
                array_key_exists(self::OLD_CONFIG_KEY, $data[self::CONFIG_KEY])
            ) {
                $data[self::CONFIG_KEY][self::NEW_CONFIG_KEY] = (array)$data[self::CONFIG_KEY][self::OLD_CONFIG_KEY];
                unset($data[self::CONFIG_KEY][self::OLD_CONFIG_KEY]);

                $queries[] = [
                    'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                    ['data' => $data, 'id' => $row['id']],
                    ['data' => Type::TARRAY, 'id' => Type::INTEGER]
                ];
            }
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getRows(LoggerInterface $logger)
    {
        $query  = 'SELECT id, data FROM oro_entity_config';
        $params = [];
        $types  = [];

        $this->logQuery($logger, $query, $params, $types);

        return $this->connection->fetchAll($query, $params, $types);
    }
}
