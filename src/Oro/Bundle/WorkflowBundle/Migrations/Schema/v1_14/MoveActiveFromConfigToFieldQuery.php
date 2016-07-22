<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MoveActiveFromConfigToFieldQuery extends ParametrizedMigrationQuery
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
            $inactivateAllQuery = 'UPDATE oro_workflow_definition SET active=:is_active WHERE name IS NOT NULL';
            $params = ['is_active' => false];
            $types = ['is_active' => 'boolean'];
            $this->logQuery($logger, $inactivateAllQuery, $params, $types);
            $this->connection->executeUpdate($inactivateAllQuery, $params, $types);

            $activateQuery = 'UPDATE oro_workflow_definition SET active=:is_active WHERE name = :workflow_name';
            $activationStatement = $this->connection->prepare($activateQuery);
            $activationStatement->bindValue(':is_active', true, 'boolean');
            $updateParamsTypes = ['workflow_name' => 'string', 'is_active' => 'boolean'];

            foreach ($this->unshiftActiveWorkflows($logger) as $workflow) {
                $this->logQuery(
                    $logger,
                    $activateQuery,
                    ['workflow_name' => $workflow, 'is_active' => true],
                    $updateParamsTypes
                );
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
        $fetchQuery = 'SELECT id, data FROM oro_entity_config config';

        $params = $types = $processedWorkflows = [];

        $this->logQuery($logger, $fetchQuery, $params, $types);

        $fetchStatement = $this->connection->executeQuery($fetchQuery, $params, $types);

        $updateQuery = 'UPDATE oro_entity_config SET data=:data WHERE id=:id';
        $updateStatement = $this->connection->prepare($updateQuery);

        while (($row = $fetchStatement->fetch()) !== null) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (array_key_exists('workflow', $data) && array_key_exists('active_workflow', $data['workflow'])) {
                if (!in_array($data['workflow']['active_workflow'], $processedWorkflows, true)) {
                    $processedWorkflows[] = $data['workflow']['active_workflow'];
                    yield $data['workflow']['active_workflow'];
                }

                unset($data['workflow']['active_workflow']);
                $updateStatement->bindValue(':data', $data, 'array');
                $updateStatement->bindValue(':id', $row['id'], 'integer');
                $this->logQuery(
                    $logger,
                    $updateQuery,
                    ['data' => $data, 'id' => $row['id']],
                    ['data' => 'array', 'id' => 'integer']
                );
                $updateStatement->execute();
            }
        }
    }
}
