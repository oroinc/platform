<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;

use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName;
use Oro\Component\DoctrineUtils\ORM\LikeQueryHelperTrait;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provide logic for workflow scheduled transitions processes synchronization
 */
class WorkflowDefinitionChangesListener implements EventSubscriberInterface
{
    use LikeQueryHelperTrait;

    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var \Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator */
    protected $processConfigurator;

    /** @var ProcessDefinitionRepository */
    protected $processDefinitionRepository;

    /** @var array */
    private $generatedConfigurations = [];

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ProcessConfigurator $processConfigurator
     * @param ProcessDefinitionRepository $processDefinitionRepository
     */
    public function __construct(
        ProcessConfigurationGenerator $generator,
        ProcessConfigurator $processConfigurator,
        ProcessDefinitionRepository $processDefinitionRepository
    ) {
        $this->generator = $generator;
        $this->processConfigurator = $processConfigurator;
        $this->processDefinitionRepository = $processDefinitionRepository;
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
            unset($this->generatedConfigurations);
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function workflowAfterDelete(WorkflowChangesEvent $event)
    {
        $this->removeRelatedProcesses($event->getDefinition());
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
        $this->removeRelatedProcesses($event->getDefinition());
    }

    /**
     * @param WorkflowDefinition $definition
     */
    protected function removeRelatedProcesses(WorkflowDefinition $definition)
    {
        $workflowScheduledProcesses = $this->getWorkflowRelatedProcessDefinitions($definition->getName());

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

        //check for removal
        $persistedProcessDefinitions = $this->getWorkflowRelatedProcessDefinitions($definition->getName());
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
     * @param string $workflowName
     * @return ProcessDefinition[]
     */
    protected function getWorkflowRelatedProcessDefinitions($workflowName)
    {
        // stpn__workflow!_name__
        $matchWorkflowRelated = implode(
            ScheduledTransitionProcessName::DELIMITER,
            [ScheduledTransitionProcessName::IDENTITY_PREFIX, $workflowName, '']
        );

        // stpn!_!_workflow!_name!_!_%  - escaped with ! like expression for all transitions
        return $this->processDefinitionRepository->findLikeName(
            $this->makeLikeParam($matchWorkflowRelated, '%s%%'),
            '!'
        );
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
