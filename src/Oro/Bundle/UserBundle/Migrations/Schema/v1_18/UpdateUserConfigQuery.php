<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateUserConfigQuery extends ParametrizedMigrationQuery
{
    /**
     * {inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    /**
     * {inheritdoc}
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
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $id => $data) {
            if (!isset($data['activity']['action_widget'])) {
                continue;
            }

            $data['grouping'] = ['groups' => 'dictionary'];
            $data['dictionary'] = [
                'virtual_fields'=> 'id',
                'search_fields' => ['firstName', 'lastName'],
                'representation_field' => 'fullName'
            ];

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $id];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
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
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = :className';
        $params = ['className' => 'Oro\Bundle\UserBundle\Entity\User'];
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params);
        foreach ($rows as $row) {
            $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
