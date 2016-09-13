<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;

class TransitionTriggersUpdater
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Overrides triggers by TriggersBag. Flushes data into database.
     * @param TriggersBag $triggersBag
     */
    public function update(TriggersBag $triggersBag)
    {
        $definition = $triggersBag->getDefinition();

        $this->remove(new TriggersBag($definition, []));

        $triggers = $triggersBag->getTriggers();

        if (count($triggers) === 0) {
            return;
        }

        $em = $this->getEntityManager();

        foreach ($triggers as $trigger) {
            //ensure definition is corresponds
            $trigger->setWorkflowDefinition($definition);

            $em->persist($trigger);
        }

        $em->flush();
    }

    /**
     * Removes triggers by TriggerBag. If TriggerBag contains no triggers it will remove all by its WorkflowDefinition
     * @param TriggersBag $triggersBag
     */
    public function remove(TriggersBag $triggersBag)
    {
        $triggers = $triggersBag->getTriggers();

        if (count($triggers) === 0) {
            $this->deleteByWorkflowDefinition($triggersBag->getDefinition());
        } else {
            //remove separate triggers that matched
        }
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
