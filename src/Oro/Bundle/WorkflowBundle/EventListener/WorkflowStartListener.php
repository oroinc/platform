<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

class WorkflowStartListener
{
    /** @var array */
    protected $entitiesScheduledForWorkflowStart = [];

    /** @var int */
    protected $deepLevel = 0;

    /** @var WorkflowManagerRegistry */
    private $workflowManagerRegistry;

    /** @var WorkflowAwareCache */
    private $cache;

    /**
     * @param WorkflowManagerRegistry $workflowManagerRegistry
     * @param WorkflowAwareCache $cache
     */
    public function __construct(
        WorkflowManagerRegistry $workflowManagerRegistry,
        WorkflowAwareCache $cache
    ) {
        $this->workflowManagerRegistry = $workflowManagerRegistry;
        $this->cache = $cache;
    }

    /**
     * Schedule workflow auto start for entity.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$this->cache->hasRelatedActiveWorkflows($entity)) {
            return;
        }

        foreach ($this->getApplicableWorkflowsForStart($entity) as $activeWorkflow) {
            if ($activeWorkflow->getStepManager()->hasStartStep()) {
                $this->entitiesScheduledForWorkflowStart[$this->deepLevel][] = new WorkflowStartArguments(
                    $activeWorkflow->getName(),
                    $entity
                );
            }
        }
    }

    /**
     * @param object $entity
     * @return array|Workflow[]
     */
    protected function getApplicableWorkflowsForStart($entity)
    {
        $applicableWorkflows = $this->getWorkflowManager(false)->getApplicableWorkflows($entity);

        // apply force autostart (ignore default filters)
        $workflows = $this->getWorkflowManager()->getApplicableWorkflows($entity);
        foreach ($workflows as $name => $workflow) {
            if (!$workflow->getDefinition()->isForceAutostart()) {
                continue;
            }
            $applicableWorkflows[$name] = $workflow;
        }

        return $applicableWorkflows;
    }

    /**
     * Execute workflow start for scheduled entities.
     */
    public function postFlush()
    {
        $currentDeepLevel = $this->deepLevel;

        if (!empty($this->entitiesScheduledForWorkflowStart[$currentDeepLevel])) {
            $this->deepLevel++;
            $massStartData = $this->entitiesScheduledForWorkflowStart[$currentDeepLevel];
            unset($this->entitiesScheduledForWorkflowStart[$currentDeepLevel]);
            $this->getWorkflowManager()->massStartWorkflow($massStartData);
            $this->deepLevel--;
        }
    }

    /**
     * @param bool $system
     * @return WorkflowManager
     */
    protected function getWorkflowManager($system = true)
    {
        return $this->workflowManagerRegistry->getManager($system ? 'system' : 'default');
    }
}
