<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses;

use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provide logic for workflow scheduled transitions processes synchronization
 */
class WorkflowDefinitionChangesListener implements EventSubscriberInterface
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

    /**
     * @param WorkflowChangesEvent $event
     */
    public function generateProcessConfigurations(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();

        $this->generatedConfigurations[$definition->getName()] = $this->generator
            ->generateForScheduledTransition($definition);
    }

    /**
     * @param WorkflowChangesEvent $event
     */
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

        $workflowName = $workflowDefinition->getName();

        if (array_key_exists($workflowName, $this->generatedConfigurations)) {
            $this->reconfigureTransitionProcesses(
                $this->generatedConfigurations[$workflowName],
                $workflowDefinition
            );
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowDeleted(WorkflowChangesEvent $event)
    {
        $this->cleanProcesses($event->getDefinition());
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowActivated(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();

        $this->reconfigureTransitionProcesses(
            $this->generator->generateForScheduledTransition($definition),
            $definition
        );
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowDeactivated(WorkflowChangesEvent $event)
    {
        $this->cleanProcesses($event->getDefinition());
    }

    /**
     * @param WorkflowDefinition $definition
     */
    protected function cleanProcesses(WorkflowDefinition $definition)
    {
        $workflowScheduledProcesses = $this->getScheduledTransitionProcesses()->workflowRelated($definition->getName());

        $toDelete = [];
        foreach ($workflowScheduledProcesses as $processDefinition) {
            $toDelete[] = $processDefinition->getName();
        }

        $this->getProcessConfigurator()->removeProcesses($toDelete);
    }

    /**
     * @param array $processConfigurations
     * @param WorkflowDefinition $definition
     */
    protected function reconfigureTransitionProcesses(array $processConfigurations, WorkflowDefinition $definition)
    {
        $processConfigurator = $this->getProcessConfigurator();

        $processConfigurator->configureProcesses($processConfigurations);
        $persistedProcessDefinitions = $this->getScheduledTransitionProcesses()->workflowRelated(
            $definition->getName()
        );

        $toDelete = [];
        foreach ($persistedProcessDefinitions as $processDefinition) {
            $name = $processDefinition->getName();
            if (!array_key_exists($name, $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS])) {
                $toDelete[] = $processDefinition->getName();
            }
        }

        $processConfigurator->removeProcesses($toDelete);
    }

    /**
     * @return ProcessConfigurator
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'generateProcessConfigurations',
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'generateProcessConfigurations',
            WorkflowEvents::WORKFLOW_CREATED => 'workflowCreated',
            WorkflowEvents::WORKFLOW_UPDATED => 'workflowUpdated',
            WorkflowEvents::WORKFLOW_DELETED => 'workflowDeleted',
            WorkflowEvents::WORKFLOW_ACTIVATED => 'workflowActivated',
            WorkflowEvents::WORKFLOW_DEACTIVATED => 'workflowDeactivated'
        ];
    }
}
