<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_3;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateWorkflowConfigurationQuery extends ParametrizedMigrationQuery
{
    const TRANSITIONS_KEY = 'transitions';

    const TRANSITION_DEFINITION_KEY = 'transition_definitions';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update cloned workflow transition configuration');
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    public function execute(LoggerInterface $logger, $dryRun = false)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                $this->connection->quoteIdentifier('name'),
                $this->connection->quoteIdentifier('configuration')
            )
            ->from('oro_workflow_definition')
            ->where($this->connection->getExpressionBuilder()->eq(
                $this->connection->quoteIdentifier('system'),
                ':isSystem'
            ))
            ->setParameter('isSystem', false, Types::BOOLEAN);

        $items = $qb->execute()->fetchAll();

        foreach ($items as $item) {
            $name = $item['name'];
            $configuration = $item['configuration'];
            $data = $configuration ? $this->connection->convertToPHPValue($configuration, Types::ARRAY) : [];
            $data = $this->updateConfig($data);
            $configuration = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

            $updateQb = $this->connection->createQueryBuilder()
                ->update('oro_workflow_definition')
                ->set($this->connection->quoteIdentifier('configuration'), ':configuration')
                ->where($this->connection->getExpressionBuilder()->eq(
                    $this->connection->quoteIdentifier('name'),
                    ':name'
                ));
            $params = ['configuration' => $configuration, 'name' => $name];
            $types = ['configuration' => Types::ARRAY, 'name' => Types::STRING];
            $this->logQuery($logger, $updateQb->getSQL(), $params, $types);
            if (!$dryRun) {
                $updateQb->setParameters($params, $types)->execute();
            }
        }
    }

    /**
     * @param array $workflowData
     * @return array
     */
    protected function updateConfig(array $workflowData)
    {
        foreach ($workflowData[self::TRANSITIONS_KEY] as $transitionName => &$transitionData) {
            if (isset($transitionData['form_options']['init_actions'])) {
                $transitionData['form_options']['form_init'] = array_merge_recursive(
                    $transitionData['form_options']['init_actions'],
                    $transitionData['form_options']['form_init'] ?? []
                );
                unset($transitionData['form_options']['init_actions']);
            }
        }
        foreach ($workflowData[self::TRANSITION_DEFINITION_KEY] as $transitionName => &$definitionData) {
            if (array_key_exists('post_actions', $definitionData)) {
                $definitionData['actions'] = array_merge_recursive(
                    $definitionData['post_actions'],
                    $definitionData['actions'] ?? []
                );
                unset($definitionData['post_actions']);
            }
            if (array_key_exists('pre_conditions', $definitionData)) {
                $definitionData['preconditions'] = array_merge_recursive(
                    $definitionData['pre_conditions'],
                    $definitionData['preconditions'] ?? []
                );
                unset($definitionData['pre_conditions']);
            }
        }

        return $workflowData;
    }
}
