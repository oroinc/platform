<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionTriggersUpdater
{
    /** @var EntityManager */
    private $em;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TransitionTriggersUpdateDecider */
    private $updateDecider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TransitionTriggersUpdateDecider $decider
     */
    public function __construct(DoctrineHelper $doctrineHelper, TransitionTriggersUpdateDecider $decider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->updateDecider = $decider;
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
            $em = $this->getEntityManager();

            foreach ($remove as $trashTrigger) {
                $em->remove($trashTrigger);
            }

            foreach ($add as $newTrigger) {
                $em->persist($newTrigger);
            }

            $em->flush();
        }
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function removeTriggers(WorkflowDefinition $workflowDefinition)
    {
        $triggers = $this->getStoredDefinitionTriggers($workflowDefinition);
        if (count($triggers) !== 0) {
            $em = $this->getEntityManager();
            foreach ($triggers as $trigger) {
                $em->remove($trigger);
            }
            $em->flush();
        }
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array|AbstractTransitionTrigger[]
     */
    private function getStoredDefinitionTriggers(WorkflowDefinition $workflowDefinition)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AbstractTransitionTrigger::class)->findBy(
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
        if ($this->em) {
            return $this->em;
        }

        return $this->em = $this->doctrineHelper->getEntityManagerForClass(AbstractTransitionTrigger::class);
    }
}
