<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class ProcessConfigurationBuilder extends AbstractConfigurationBuilder
{
    /**
     * @param array $configuration
     * @return ProcessDefinition[]
     */
    public function buildProcessDefinitions(array $configuration)
    {
        $definitions = array();
        foreach ($configuration as $name => $definitionConfiguration) {
            $definitions[] = $this->buildProcessDefinition($name, $definitionConfiguration);
        }

        return $definitions;
    }

    /**
     * @param $name
     * @param array $configuration
     * @return ProcessDefinition
     */
    public function buildProcessDefinition($name, array $configuration)
    {
        $this->assertConfigurationOptions($configuration, array('label', 'entity'));

        $enabled = $this->getConfigurationOption($configuration, 'enabled', true);
        $order = $this->getConfigurationOption($configuration, 'order', 0);
        $excludeDefinitions = $this->getConfigurationOption($configuration, 'exclude_definitions', array());
        $actionsConfiguration = $this->getConfigurationOption($configuration, 'actions_configuration', array());
        $preConditionsConfiguration = $this->getConfigurationOption($configuration, 'pre_conditions', array());

        $definition = new ProcessDefinition();
        $definition
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setRelatedEntity($configuration['entity'])
            ->setEnabled($enabled)
            ->setExecutionOrder($order)
            ->setExcludeDefinitions($excludeDefinitions)
            ->setActionsConfiguration($actionsConfiguration)
            ->setPreConditionsConfiguration($preConditionsConfiguration);

        return $definition;
    }

    /**
     * @param array $configuration
     * @param ProcessDefinition[] $definitionsByName
     * @return ProcessTrigger[]
     * @throws \LogicException
     */
    public function buildProcessTriggers(array $configuration, array $definitionsByName)
    {
        $triggers = array();
        foreach ($configuration as $definitionName => $triggersConfiguration) {
            if (empty($definitionsByName[$definitionName])) {
                throw new \LogicException(sprintf('Process definition "%s" not found', $definitionName));
            }

            foreach ($triggersConfiguration as $triggerConfiguration) {
                $triggers[] = $this->buildProcessTrigger($triggerConfiguration, $definitionsByName[$definitionName]);
            }
        }

        return $triggers;
    }

    /**
     * @param array $configuration
     * @param ProcessDefinition $definition
     * @return ProcessTrigger
     * @throws InvalidParameterException
     */
    public function buildProcessTrigger(array $configuration, ProcessDefinition $definition)
    {
        $this->assertConfigurationOptions($configuration, array('event'));
        $event = $configuration['event'];
        if (!in_array($event, ProcessTrigger::getAllowedEvents())) {
            throw new InvalidParameterException(sprintf('Event "%s" is not allowed', $event));
        }

        $field     = $this->getConfigurationOption($configuration, 'field', null);
        $priority  = $this->getConfigurationOption($configuration, 'priority', Job::PRIORITY_DEFAULT);
        $queued    = $this->getConfigurationOption($configuration, 'queued', false);
        $timeShift = $this->getConfigurationOption($configuration, 'time_shift', null);

        if ($timeShift && !is_int($timeShift) && !$timeShift instanceof \DateInterval) {
            throw new InvalidParameterException('Time shift parameter must be either integer or DateInterval');
        }

        if ($field && $event != ProcessTrigger::EVENT_UPDATE) {
            throw new InvalidParameterException('Field is only allowed for update event');
        }

        $trigger = new ProcessTrigger();
        $trigger
            ->setEvent($event)
            ->setField($field)
            ->setPriority($priority)
            ->setQueued($queued)
            ->setDefinition($definition);

        if ($timeShift instanceof \DateInterval) {
            $trigger->setTimeShiftInterval($timeShift);
        } else {
            $trigger->setTimeShift($timeShift);
        }

        return $trigger;
    }
}
