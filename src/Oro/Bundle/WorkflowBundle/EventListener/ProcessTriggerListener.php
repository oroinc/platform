<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessCronScheduler;

class ProcessTriggerListener
{
    /**
     * @var ProcessCronScheduler
     */
    private $processTriggerScheduler;

    public function __construct(ProcessCronScheduler $processTriggerScheduler)
    {
        $this->processTriggerScheduler = $processTriggerScheduler;
    }

    public function postRemove(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();
        if (!$entity instanceof ProcessTrigger) {
            return;
        }

        $this->processTriggerScheduler->remove($entity);
    }

    public function postFlush()
    {
        $this->processTriggerScheduler->flush();
    }
}
