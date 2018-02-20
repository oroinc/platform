<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_3;

use Doctrine\DBAL\Types\Type;
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
        $items = $this->connection->fetchAll(
            'SELECT name, configuration FROM oro_workflow_definition WHERE system = false'
        );

        foreach ($items as $item) {
            $name = $item['name'];
            $configuration = $item['configuration'];
            $data = $configuration ? $this->connection->convertToPHPValue($configuration, Type::TARRAY) : [];
            $data = $this->updateConfig($data);
            $configuration = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

            $query = 'UPDATE oro_workflow_definition SET configuration = ? WHERE name = ?';
            $params = [$configuration, $name];

            $this->logQuery($logger, $query, $params);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params);
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
