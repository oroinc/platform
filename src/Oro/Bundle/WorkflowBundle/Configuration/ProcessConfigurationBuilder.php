<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Cron\CronExpression;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * The builder for process definitions.
 */
class ProcessConfigurationBuilder
{
    /**
     * @param array $configuration
     * @return ProcessDefinition[]
     */
    public function buildProcessDefinitions(array $configuration)
    {
        $definitions = [];
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
        if (!isset($configuration['entity'])) {
            throw new MissedRequiredOptionException('The "entity" configuration option is required.');
        }
        if (!isset($configuration['label'])) {
            throw new MissedRequiredOptionException('The "label" configuration option is required.');
        }

        $enabled = $configuration['enabled'] ?? true;
        $order = $configuration['order'] ?? 0;
        $excludeDefinitions = $configuration['exclude_definitions'] ?? [];
        $actionsConfiguration = $configuration['actions_configuration'] ?? [];
        $preConditionsConfiguration = $configuration['preconditions'] ?? [];

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
        $triggers = [];
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
        $event = $configuration['event'] ?? null;
        $cron = $this->getCronExpression($configuration);

        $this->validateEventAndCronParameters($event, $cron);

        $field = $configuration['field'] ?? null;
        $priority = $configuration['priority'] ?? ProcessPriority::PRIORITY_DEFAULT;
        $queued = $configuration['queued'] ?? false;
        $timeShift = $configuration['time_shift'] ?? null;

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
    private function getCronExpression(array $configuration)
    {
        $cron = $configuration['cron'] ?? null;
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
    private function validateEventAndCronParameters($event, $cron)
    {
        if ($cron && $event) {
            throw new InvalidParameterException('Only one parameter "event" or "cron" must be configured.');
        }

        if (!$cron && !in_array($event, ProcessTrigger::getAllowedEvents(), true)) {
            throw new InvalidParameterException(sprintf('Event "%s" is not allowed', $event));
        }
    }
}
