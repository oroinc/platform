<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Cron\TransitionTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionTriggersUpdater
{
    /** @var EntityManager */
    private $em;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TransitionTriggersUpdateDecider */
    private $updateDecider;

    /** @var TransitionTriggerCronScheduler */
    private $cronScheduler;

    /** @var EventTriggerCache */
    private $cache;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TransitionTriggersUpdateDecider $decider
     * @param TransitionTriggerCronScheduler $cronScheduler
     * @param EventTriggerCache $cache
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TransitionTriggersUpdateDecider $decider,
        TransitionTriggerCronScheduler $cronScheduler,
        EventTriggerCache $cache
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->updateDecider = $decider;
        $this->cronScheduler = $cronScheduler;
        $this->cache = $cache;
    }

    /**
     * Overrides triggers by TriggersBag. Flushes data into database.
     * @param TriggersBag $triggersBag
     */
    public function updateTriggers(TriggersBag $triggersBag)
    {
        $definition = $triggersBag->getDefinition();

        $existingTriggers = $this->getStoredDefinitionTriggers($definition);

        list($add, $remove) = $this->updateDecider->decide($existingTriggers, $triggersBag->getTriggers());

        if (count($remove) !== 0 || count($add) !== 0) {
            foreach ($remove as $trashTrigger) {
                $this->remove($trashTrigger);
            }

            foreach ($add as $newTrigger) {
                $this->persist($newTrigger);
            }

            $this->flush();
        }
    }

    /**
     * @param BaseTransitionTrigger $trigger
     */
    private function persist(BaseTransitionTrigger $trigger)
    {
        $this->getEntityManager()->persist($trigger);

        if ($trigger instanceof TransitionCronTrigger) {
            $this->cronScheduler->addSchedule($trigger);
        }
    }

    /**
     * @param BaseTransitionTrigger $trigger
     */
    private function remove(BaseTransitionTrigger $trigger)
    {
        $this->getEntityManager()->remove($trigger);

        if ($trigger instanceof TransitionCronTrigger) {
            $this->cronScheduler->removeSchedule($trigger);
        }
    }

    /**
     * @return void
     */
    private function flush()
    {
        $this->getEntityManager()->flush();
        $this->cronScheduler->flush();
        $this->cache->build();
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function removeTriggers(WorkflowDefinition $workflowDefinition)
    {
        $triggers = $this->getStoredDefinitionTriggers($workflowDefinition);
        if (count($triggers) !== 0) {
            foreach ($triggers as $trigger) {
                $this->remove($trigger);
            }
            $this->flush();
        }
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array|BaseTransitionTrigger[]
     */
    private function getStoredDefinitionTriggers(WorkflowDefinition $workflowDefinition)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(BaseTransitionTrigger::class)->findBy(
            [
                'workflowDefinition' => $workflowDefinition->getName()
            ]
        );
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->doctrineHelper->getEntityManagerForClass(BaseTransitionTrigger::class);
        }

        return $this->em;
    }
}
