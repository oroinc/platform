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

/**
 * Provide logic for workflow scheduled transitions processes synchronization
 */
class WorkflowDefinitionChangesListener implements EventSubscriberInterface
{
    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var \Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator */
    protected $processConfigurator;

    /** @var ScheduledTransitionProcesses */
    protected $scheduledTransitionProcesses;

    /** @var array */
    private $generatedConfigurations = [];

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ProcessConfigurator $processConfigurator
     * @param ScheduledTransitionProcesses $scheduledTransitionProcesses
     */
    public function __construct(
        ProcessConfigurationGenerator $generator,
        ProcessConfigurator $processConfigurator,
        ScheduledTransitionProcesses $scheduledTransitionProcesses
    ) {
        $this->generator = $generator;
        $this->processConfigurator = $processConfigurator;
        $this->scheduledTransitionProcesses = $scheduledTransitionProcesses;
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
    public function workflowAfterCreate(WorkflowChangesEvent $event)
    {
        $workflowName = $event->getDefinition()->getName();

        if (array_key_exists($workflowName, $this->generatedConfigurations)) {
            $this->processConfigurator->configureProcesses($this->generatedConfigurations[$workflowName]);
            unset($this->generatedConfigurations[$workflowName]);
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowAfterUpdate(WorkflowChangesEvent $event)
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
    public function workflowAfterDelete(WorkflowChangesEvent $event)
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
        $workflowScheduledProcesses = $this->scheduledTransitionProcesses->workflowRelated($definition->getName());

        $toDelete = [];
        foreach ($workflowScheduledProcesses as $processDefinition) {
            $toDelete[] = $processDefinition->getName();
        }

        $this->processConfigurator->removeProcesses($toDelete);
    }

    /**
     * @param array $processConfigurations
     * @param WorkflowDefinition $definition
     */
    protected function reconfigureTransitionProcesses(array $processConfigurations, WorkflowDefinition $definition)
    {
        $this->processConfigurator->configureProcesses($processConfigurations);
        $persistedProcessDefinitions = $this->scheduledTransitionProcesses->workflowRelated(
            $definition->getName()
        );

        $toDelete = [];
        foreach ($persistedProcessDefinitions as $processDefinition) {
            $name = $processDefinition->getName();
            if (!array_key_exists($name, $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS])) {
                $toDelete[] = $processDefinition->getName();
            }
        }

        $this->processConfigurator->removeProcesses($toDelete);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'generateProcessConfigurations',
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'generateProcessConfigurations',
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'workflowAfterCreate',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'workflowAfterUpdate',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'workflowAfterDelete',
            WorkflowEvents::WORKFLOW_ACTIVATED => 'workflowActivated',
            WorkflowEvents::WORKFLOW_DEACTIVATED => 'workflowDeactivated'
        ];
    }
}
