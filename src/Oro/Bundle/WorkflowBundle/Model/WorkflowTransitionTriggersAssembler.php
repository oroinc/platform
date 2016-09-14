<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;

class WorkflowTransitionTriggersAssembler
{
    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array|AbstractTransitionTrigger[]
     */
    public function assembleTriggers(WorkflowDefinition $workflowDefinition)
    {
        $configuration = $workflowDefinition->getConfiguration();

        /**@var array $transitions */
        $transitions = $this->getOption($configuration, WorkflowConfiguration::NODE_TRANSITIONS, []);

        $triggers = [];

        foreach ($transitions as $transitionName => $transition) {
            /**
             * @var array $transition
             * @var array $triggersConfiguration
             */

            $triggersConfiguration = $this->getOption($transition, WorkflowConfiguration::NODE_TRANSITION_TRIGGERS, []);

            foreach ($triggersConfiguration as $triggerConfig) {
                $triggers[] = $this->assembleTrigger($triggerConfig, $transitionName, $workflowDefinition);
            }
        }

        return $triggers;
    }

    /**
     * @param array $options trigger options array
     * @param string $transitionName
     * @param WorkflowDefinition $workflowDefinition
     * @return AbstractTransitionTrigger
     * @throws AssemblerException
     */
    protected function assembleTrigger(array $options, $transitionName, WorkflowDefinition $workflowDefinition)
    {
        if (empty($options['event']) && empty($options['cron'])) {
            throw new AssemblerException(
                sprintf(
                    'Either `event` or `cron` type of triggers must be specified.' .
                    'Got none in `%s` workflow configuration for `%s` transition triggers.',
                    $workflowDefinition->getName(),
                    $transitionName
                )
            );
        }

        /**@var AbstractTransitionTrigger $trigger */
        $trigger = !empty($options['event'])
            ? $this->createEventTrigger($options, $workflowDefinition)
            : $this->createCronTrigger($options);

        return $trigger
            ->setWorkflowDefinition($workflowDefinition)
            ->setTransitionName($transitionName)
            ->setQueued($this->getOption($options, 'queued', true));
    }

    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @return TransitionTriggerEvent
     */
    private function createEventTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        $trigger = new TransitionTriggerEvent();

        $trigger->setEntityClass(
            !empty($options['entity_class']) ? $options['entity_class'] : $workflowDefinition->getRelatedEntity()
        );

        $trigger
            ->setEvent($options['event'])
            ->setField($this->getOption($options, 'field', null))
            ->setRelation($this->getOption($options, 'relation', null))
            ->setRequire($this->getOption($options, 'require', null));

        return $trigger;
    }

    /**
     * @param array $options
     * @return TransitionTriggerCron
     */
    private function createCronTrigger(array $options)
    {
        $trigger = new TransitionTriggerCron();

        return $trigger
            ->setCron($options['cron'])
            ->setFilter($this->getOption($options, 'filter', null))
            ->setQueued($this->getOption($options, 'queued', true));
    }

    /**
     * @param array $options
     * @param string $optionName
     * @param mixed $default
     * @return mixed
     */
    private function getOption(array $options, $optionName, $default = null)
    {
        return array_key_exists($optionName, $options) ? $options[$optionName] : $default;
    }
}
