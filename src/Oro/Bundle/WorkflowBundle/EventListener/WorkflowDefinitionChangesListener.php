<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses;

use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provide logic for workflow scheduled transitions processes synchronization
 */
class WorkflowDefinitionChangesListener
{
    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var ServiceLink */
    protected $processConfiguratorLink;

    /** @var ScheduledTransitionProcesses */
    protected $scheduledTransitionProcessesLink;

    /** @var array */
    private $generatedConfigurations = [];

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ServiceLink $processConfiguratorServiceLink
     * @param serviceLink $scheduledTransitionProcessesLink
     */
    public function __construct(
        ProcessConfigurationGenerator $generator,
        ServiceLink $processConfiguratorServiceLink,
        ServiceLink $scheduledTransitionProcessesLink
    ) {
        $this->generator = $generator;
        $this->processConfiguratorLink = $processConfiguratorServiceLink;
        $this->scheduledTransitionProcessesLink = $scheduledTransitionProcessesLink;
    }

    public function generateProcessConfigurations(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();

        $this->generatedConfigurations[$definition->getName()] = $this->generator
            ->generateForScheduledTransition($definition);
    }

    public function workflowCreated(WorkflowChangesEvent $event)
    {
        $workflowName = $event->getDefinition()->getName();

        if (array_key_exists($workflowName, $this->generatedConfigurations)) {
            $this->getProcessConfigurator()->configureProcesses($this->generatedConfigurations[$workflowName]);
            unset($this->generatedConfigurations[$workflowName]);
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowUpdated(WorkflowChangesEvent $event)
    {
        $workflowDefinition = $event->getDefinition();
        
        $workflowName = $event->getDefinition()->getName();

        $processConfigurator = $this->getProcessConfigurator();
        
        $scheduledTransitionProcesses = $this->getScheduledTransitionProcesses();
        
        if(array_key_exists($workflowName, $this->generatedConfigurations)) {
            
            $processConfigurator->configureProcesses($workflowDefinition);
            $persistedProcessDefinitions = $scheduledTransitionProcesses->workflowRelated(
                $workflowDefinition->getName()
            );
            
            $generated = $this->generatedConfigurations[$workflowName];

            $toDelete = [];
            foreach ($persistedProcessDefinitions as $processDefinition) {
                $name = $processDefinition->getName();
                if (!array_key_exists($name, $generated[ProcessConfigurationProvider::NODE_DEFINITIONS])) {
                    $toDelete[] = $processDefinition->getName();
                }
            }

            $processConfigurator->removeProcesses($toDelete);
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowDeleted(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();

        $workflowScheduledProcesses = $this->getScheduledTransitionProcesses()->workflowRelated($definition->getName());

        $toDelete = [];
        foreach ($workflowScheduledProcesses as $processDefinition) {
            $toDelete[] = $processDefinition->getName();
        }

        $this->getProcessConfigurator()->removeProcesses($toDelete);
    }

    /**
     * @return ProcessConfigurator
     */
    protected function getProcessConfigurator()
    {
        $service = $this->processConfiguratorLink->getService();

        if (!$service instanceof ProcessConfigurator) {
            throw new \RuntimeException(
                'Instance of Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator expected.'
            );
        }

        return $service;
    }

    /**
     * @return ScheduledTransitionProcesses
     */
    protected function getScheduledTransitionProcesses()
    {
        $service = $this->scheduledTransitionProcessesLink->getService();

        if (!$service instanceof ScheduledTransitionProcesses) {
            throw new \RuntimeException(
                'Instance of Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses expected.'
            );
        }

        return $service;
    }
}
