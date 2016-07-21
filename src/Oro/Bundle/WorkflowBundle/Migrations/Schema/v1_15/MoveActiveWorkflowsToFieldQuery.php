<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MoveActiveWorkflowsToFieldQuery extends ParametrizedMigrationQuery
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Moves from entities config "active_workflows" to corresponded workflow "active" field.';
    }

    /**
     * @param LoggerInterface $logger
     */
    public function execute(LoggerInterface $logger)
    {
        $configs = $this->getEntitiesConfigs($logger);

        $activeWorkflows = $this->extractWorkflows($configs);

        //inactive
        $this->connection->executeQuery(
            'UPDATE oro_workflow_definition SET active=:is_active WHERE name NOT IN (:active_workflows)',
            ['active_workflows' => $activeWorkflows, 'is_active' => false],
            ['active_workflows' => Connection::PARAM_STR_ARRAY, 'is_active' => 'boolean']
        );

        //active
        $this->connection->executeQuery(
            'UPDATE oro_workflow_definition SET active=:is_active WHERE name IN (:active_workflows)',
            ['active_workflows' => $activeWorkflows, 'is_active' => true],
            ['active_workflows' => Connection::PARAM_STR_ARRAY, 'is_active' => 'boolean']
        );
    }

    /**
     * @param array $configs
     * @return array
     */
    private function extractWorkflows(array $configs)
    {
        $activeWorkflows = [];
        foreach ($configs as $config) {
            $data = $this->connection->convertToDatabaseValue($config['data'], 'array');
            if (array_key_exists('workflow', $data) && array_key_exists('active_workflows', $data['workflow'])) {
                $activeWorkflows = array_merge($activeWorkflows, $data['workflow']['active_workflows']);
            }
        }

        return array_unique($activeWorkflows);
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    private function getEntitiesConfigs(LoggerInterface $logger)
    {
        $query = 'SELECT config.id, config.data from oro_entity_config config
          INNER JOIN oro_workflow_definition workflow on workflow.related_entity = config.class_name';

        $params = [];
        $types = [];

        $this->logQuery($logger, $query, $params, $types);

        return $this->connection->fetchAll($query, $params, $types);
    }
}
