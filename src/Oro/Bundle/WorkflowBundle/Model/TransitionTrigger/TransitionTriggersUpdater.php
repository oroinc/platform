<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Cron\TransitionTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Provides methods to update and remove transition triggers.
 */
class TransitionTriggersUpdater
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private TransitionTriggersUpdateDecider $updateDecider,
        private TransitionTriggerCronScheduler $cronScheduler,
        private EventTriggerCache $cache
    ) {
    }

    /**
     * Overrides triggers by TriggersBag. Flushes data into database.
     */
    public function updateTriggers(TriggersBag $triggersBag): void
    {
        $this->updateTransitionTriggers($triggersBag);
        $this->updateCronSchedules($triggersBag->getDefinition());
    }

    public function removeTriggers(WorkflowDefinition $workflowDefinition): void
    {
        $triggers = $this->getStoredDefinitionTriggers($workflowDefinition);
        if (\count($triggers) !== 0) {
            foreach ($triggers as $trigger) {
                $this->remove($trigger);
            }
            $this->flush();
        }
    }

    private function updateTransitionTriggers(TriggersBag $triggersBag): void
    {
        $existingTriggers = $this->getStoredDefinitionTriggers($triggersBag->getDefinition());

        [$add, $remove] = $this->updateDecider->decide($existingTriggers, $triggersBag->getTriggers());

        if (\count($remove) !== 0 || \count($add) !== 0) {
            foreach ($remove as $trashTrigger) {
                $this->remove($trashTrigger);
            }

            foreach ($add as $newTrigger) {
                $this->persist($newTrigger);
            }

            $this->flush();
        }
    }

    private function updateCronSchedules(WorkflowDefinition $definition): void
    {
        $cronTriggers = $this->getStoredDefinitionCronTriggers($definition);
        if (empty($cronTriggers)) {
            return;
        }

        foreach ($cronTriggers as $trigger) {
            $this->cronScheduler->addSchedule($trigger);
        }

        $this->cronScheduler->flush();
    }

    private function persist(BaseTransitionTrigger $trigger): void
    {
        $this->getEntityManager()->persist($trigger);
    }

    private function remove(BaseTransitionTrigger $trigger): void
    {
        $this->getEntityManager()->remove($trigger);
        if ($trigger instanceof TransitionCronTrigger) {
            $this->cronScheduler->removeSchedule($trigger);
        }
    }

    private function flush(): void
    {
        $this->getEntityManager()->flush();
        $this->cronScheduler->flush();
        $this->cache->build();
    }

    /**
     * @return BaseTransitionTrigger[]
     */
    private function getStoredDefinitionTriggers(WorkflowDefinition $workflowDefinition): array
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(BaseTransitionTrigger::class)->findBy([
            'workflowDefinition' => $workflowDefinition->getName()
        ]);
    }

    /**
     * @return BaseTransitionTrigger[]
     */
    private function getStoredDefinitionCronTriggers(WorkflowDefinition $workflowDefinition): array
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(TransitionCronTrigger::class)->findBy([
            'workflowDefinition' => $workflowDefinition->getName(),
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrineHelper->getEntityManagerForClass(BaseTransitionTrigger::class);
    }
}
