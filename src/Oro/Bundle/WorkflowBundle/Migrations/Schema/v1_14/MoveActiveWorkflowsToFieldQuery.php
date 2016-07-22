<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

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
        return 'Moves from entities config "active_workflow" to corresponded workflow "active" field.';
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
            if (array_key_exists('workflow', $data) && array_key_exists('active_workflow', $data['workflow'])) {
                if (!array_key_exists($data['workflow']['active_workflow'], $workflows)) {
                    $workflows[$data['workflow']['active_workflow']] = null;
                    yield $data['workflow']['active_workflow'];
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
}
