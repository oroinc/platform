<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Cron\CronExpression;
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
        $event = $this->getConfigurationOption($configuration, 'event', null);
        $cron = $this->getCronExpression($configuration);

        $this->validateEventAndCronParameters($event, $cron);

        $field     = $this->getConfigurationOption($configuration, 'field', null);
        $priority  = $this->getConfigurationOption($configuration, 'priority', Job::PRIORITY_DEFAULT);
        $queued    = $this->getConfigurationOption($configuration, 'queued', false);
        $timeShift = $this->getConfigurationOption($configuration, 'time_shift', null);

        if ($timeShift && !is_int($timeShift) && !$timeShift instanceof \DateInterval) {
            throw new InvalidParameterException('Time shift parameter must be either integer or DateInterval');
        }

        if ($field && $event !== ProcessTrigger::EVENT_UPDATE) {
            throw new InvalidParameterException('Field is only allowed for update event');
        }

        $trigger = new ProcessTrigger();
        $trigger
            ->setEvent($event)
            ->setField($field)
            ->setPriority($priority)
            ->setQueued($queued)
            ->setDefinition($definition)
            ->setCron($cron);

        if ($timeShift instanceof \DateInterval) {
            $trigger->setTimeShiftInterval($timeShift);
        } else {
            $trigger->setTimeShift($timeShift);
        }

        return $trigger;
    }

    /**
     * @param array $configuration
     * @return string|null
     */
    protected function getCronExpression(array $configuration)
    {
        $cron = $this->getConfigurationOption($configuration, 'cron', null);
        if ($cron !== null) {
            // validate cron expression
            CronExpression::factory($cron);
        }

        return $cron;
    }

    /**
     * @param string $event
     * @param string $cron
     * @throws InvalidParameterException
     */
    protected function validateEventAndCronParameters($event, $cron)
    {
        if ($cron && $event) {
            throw new InvalidParameterException('Only one parameter "event" or "cron" must be configured.');
        }

        if (!$cron && !in_array($event, ProcessTrigger::getAllowedEvents(), true)) {
            throw new InvalidParameterException(sprintf('Event "%s" is not allowed', $event));
        }
    }
}
