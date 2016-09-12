<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\Query;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerExpressionFactory
{
    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }


    /**
     * @param TransitionTriggerEvent $trigger
     * @param object $entity
     * @return null|string
     */
    public function create(TransitionTriggerEvent $trigger, $entity)
    {
        $filter = $trigger->getRequire();
        if (!$filter) {
            return null;
        }
        $language = new ExpressionLanguage();
        $workflowDefinition = $trigger->getWorkflowDefinition();
        if ($relation = $trigger->getRelation()) {
            $mainEntity = $language->evaluate(
                $trigger->getRelation(),
                ['entity' => $entity]
            );
            $mainEntityClass = $workflowDefinition->getRelatedEntity();
            if (!$mainEntity instanceof $mainEntityClass) {
                throw new \RuntimeException(
                    sprintf('Can\'t get main entity using relation "%s"', $relation)
                );
            }
        } else {
            $mainEntity = $entity;
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($mainEntity, $workflowDefinition->getName());

        return $language->compile(
            $filter,
            [
                'wd' => $workflowDefinition,
                'wi' => $workflowItem,
                'entity' => $entity,
                'mainEntity' => $mainEntity,
            ]
        );
    }
}
