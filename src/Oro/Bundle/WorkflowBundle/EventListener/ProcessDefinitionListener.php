<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessCronScheduler;

class ProcessDefinitionListener
{
    /** @var ProcessCronScheduler */
    private $processTriggerScheduler;

    /** @var ProcessTriggerRepository */
    private $triggerRepository;

    /** @var array */
    private $mentioned = [];

    /** @var array|ProcessTrigger[] */
    private $confirmed = [];

    public function __construct(
        ProcessCronScheduler $processTriggerScheduler,
        ProcessTriggerRepository $triggerRepository
    ) {
        $this->processTriggerScheduler = $processTriggerScheduler;
        $this->triggerRepository = $triggerRepository;
    }

    public function preRemove(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();
        if (!$entity instanceof ProcessDefinition) {
            return;
        }

        $definitionName = $entity->getName();
        $this->mentioned[$definitionName] = [];

        foreach ($this->triggerRepository->findByDefinition($entity) as $trigger) {
            $this->mentioned[$definitionName][] = $trigger;
        }
    }

    public function postRemove(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();

        if (!$entity instanceof ProcessDefinition) {
            return;
        }
        
        $processName = $entity->getName();

        if (array_key_exists($processName, $this->mentioned)) {
            $this->confirmed = array_merge($this->confirmed, $this->mentioned[$processName]);
        }
    }

    public function postFlush()
    {
        foreach ($this->mentioned as $trigger) {
            $this->processTriggerScheduler->remove($trigger);
        }
        $this->processTriggerScheduler->flush();
    }
}
