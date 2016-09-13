<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionTriggersUpdater
{
    /**
     * @var EntityManager
     */
    private $emCache;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var TransitionTriggersUpdateDecider
     */
    private $updateDecider;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->updateDecider = new TransitionTriggersUpdateDecider();
    }

    /**
     * Overrides triggers by TriggersBag. Flushes data into database.
     * @param TriggersBag $triggersBag
     */
    public function update(TriggersBag $triggersBag)
    {
        $definition = $triggersBag->getDefinition();

        $triggers = $triggersBag->getTriggers();

        $existingTriggers = $this->getStoredDefinitionTriggers($definition);

        list($add, $remove) = $this->updateDecider->decide($existingTriggers, $triggers);

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
     * @return array|AbstractTransitionTrigger
     */
    private function getStoredDefinitionTriggers(WorkflowDefinition $workflowDefinition)
    {
        return $this->doctrineHelper->getEntityRepository(AbstractTransitionTrigger::class)->findBy(
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
        if ($this->emCache) {
            return $this->emCache;
        }

        return $this->emCache = $this->doctrineHelper->getEntityManagerForClass(AbstractTransitionTrigger::class);
    }
}
