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
     * @throws \Exception
     */
    public function execute(LoggerInterface $logger)
    {
        $this->connection->beginTransaction();
        try {
            //inactivate all
            $this->connection->executeUpdate(
                'UPDATE oro_workflow_definition SET active=:is_active WHERE name IS NOT NULL',
                ['is_active' => false],
                ['is_active' => 'boolean']
            );

            $activationStatement = $this->connection->prepare(
                'UPDATE oro_workflow_definition SET active=:is_active WHERE name = :workflow_name'
            );

            $activationStatement->bindValue(':is_active', true, 'boolean');

            foreach ($this->unshiftActiveWorkflows($logger) as $workflow) {
                $activationStatement->bindValue(':workflow_name', $workflow, 'string');
                $activationStatement->execute();
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return \Generator
     */
    private function unshiftActiveWorkflows(LoggerInterface $logger)
    {
        $query = 'SELECT id, data FROM oro_entity_config config';

        $params = $types = $workflows = [];

        $this->logQuery($logger, $query, $params, $types);

        $statement = $this->connection->executeQuery($query, $params, $types);

        while (($row = $statement->fetch()) !== null) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (array_key_exists('workflow', $data) && array_key_exists('active_workflows', $data['workflow'])) {
                foreach ($data['workflow']['active_workflows'] as $workflow) {
                    if (!array_key_exists($workflow, $workflows)) {
                        $workflows[$workflow] = null;
                        yield $workflow;
                    }
                }
                unset($data['workflow']);
                $this->connection->update(
                    'oro_entity_config',
                    ['data' => $data],
                    ['id' => $row['id']],
                    [
                        'data' => 'array',
                        'id' => 'integer'
                    ]
                );
            }
        }
    }

    /**
     * @param $activeWorkflows
     */
    private function updateWorkflowDefinitions($activeWorkflows)
    {
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
}
