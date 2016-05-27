<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class ProcessConfigurationGenerator
{
    /** @var TriggerScheduleOptionsVerifier */
    private $verifier;

    /** @var string */
    private $workflowItemEntityClass;

    /**
     * @param TriggerScheduleOptionsVerifier $verifier
     * @param string $workflowItemEntityClass
     */
    public function __construct(TriggerScheduleOptionsVerifier $verifier, $workflowItemEntityClass)
    {
        $this->verifier = $verifier;
        $this->workflowItemEntityClass = $workflowItemEntityClass;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function generateForScheduledTransition(WorkflowDefinition $workflowDefinition)
    {
        $processConfigurations = [];
        $workflowName = $workflowDefinition->getName();
        foreach ($this->getTransitionsConfigurations($workflowDefinition) as $name => $transitionConfiguration) {
            if (array_key_exists('schedule', $transitionConfiguration)) {
                $this->verifier->verify($transitionConfiguration['schedule'], $workflowDefinition, $name);

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $processConfigurations = array_merge_recursive(
                    $processConfigurations,
                    $this->createProcessConfiguration(
                        $workflowName,
                        $name,
                        $transitionConfiguration['schedule']['cron']
                    )
                );
            }
        }

        return $processConfigurations;
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @param string $cronExpression
     * @return array
     */
    private function createProcessConfiguration($workflowName, $transitionName, $cronExpression)
    {
        $processName = $this->generateScheduledTransitionProcessName($workflowName, $transitionName);

        $definitionConfiguration = [
            $processName => [
                'label' => sprintf('Scheduled transition "%s"', $processName),
                'entity' => $this->workflowItemEntityClass,
                'order' => 0,
                'exclude_definitions' => [$processName],
                'actions_configuration' => [
                    '@run_action_group' => [
                        'action_group' => 'oro_workflow_transition_process_schedule',
                        'parameters' => [
                            'workflowName' => $workflowName,
                            'transitionName' => $transitionName
                        ]
                    ]
                ],
                'pre_conditions' => []
            ]
        ];

        $triggerConfiguration = [
            $processName => [['cron' => $cronExpression]]
        ];

        return [
            ProcessConfigurationProvider::NODE_DEFINITIONS => $definitionConfiguration,
            ProcessConfigurationProvider::NODE_TRIGGERS => $triggerConfiguration
        ];
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    private function getTransitionsConfigurations(WorkflowDefinition $workflowDefinition)
    {
        $config = $workflowDefinition->getConfiguration();

        return isset($config[WorkflowConfiguration::NODE_TRANSITIONS]) ?
            $config[WorkflowConfiguration::NODE_TRANSITIONS] : [];
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @return string
     */
    protected function generateScheduledTransitionProcessName($workflowName, $transitionName)
    {
        return sprintf('%s_%s_schedule_process', $workflowName, $transitionName);
    }
}
