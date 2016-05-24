<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class ProcessConfigurationGenerator
{
    /**
     * @var TriggerScheduleOptionsVerifier
     */
    private $verifier;

    /**
     * @var string
     */
    private $workflowItemEntityClass;

    /**
     * @var WorkflowAssembler
     */
    private $workflowAssembler;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param TriggerScheduleOptionsVerifier $verifier
     * @param string $workflowItemEntityClass
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        TriggerScheduleOptionsVerifier $verifier,
        $workflowItemEntityClass
    ) {
        $this->verifier = $verifier;
        $this->workflowItemEntityClass = $workflowItemEntityClass;
        $this->workflowAssembler = $workflowAssembler;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     * @internal param WorkflowDefinition $transitions
     * @internal param Workflow $workflow
     */
    public function generateForScheduledTransition(WorkflowDefinition $workflowDefinition)
    {
        $processConfigurations = [];
        foreach ($this->getTransitionsConfigurations($workflowDefinition) as $transitionConfiguration) {
            if (array_key_exists('schedule', $transitionConfiguration)) {
                //todo verify
                $processConfigurations = array_merge_recursive(
                    $processConfigurations,
                    $this->createProcessConfiguration(
                        $transitionConfiguration,
                        $workflowDefinition->getName()
                    )
                );
            }
        }

        return $processConfigurations;
    }
    /**
     * @param array $transitionConfiguration
     * @param string $workflowName
     * @return array
     */
    private function createProcessConfiguration(array $transitionConfiguration, $workflowName)
    {
        $processName = $this->generateScheduledTransitionProcessName($workflowName, $transitionConfiguration['name']);

        $definitionConfiguration = [
            $processName => [
                'Label' => sprintf('Scheduled Transition "%s"', $processName),
                'entity' => $this->workflowItemEntityClass,
                'order' => 0,
                'exclude_definitions' => [$processName],
                'actions_configuration' => [
                    '@run_action_group' => [
                        'action_group' => ''
                    ]
                ],
                'pre_conditions' => []
            ]
        ];

        $triggerConfiguration = [
            $processName => [['cron' => $transitionConfiguration['schedule']['cron']]]
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
        return sprintf('%s_%s_process', $workflowName, $transitionName);
    }
}
