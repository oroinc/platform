<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ItemsFetcher
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var TransitionQueryFactory */
    private $queryFactory;

    /** @var EntityRepository */
    private $workflowItemRepository;

    /**
     * @param TransitionQueryFactory $queryFactory
     * @param WorkflowManager $workflowManager
     * @param EntityRepository $workflowItemRepository
     */
    public function __construct(
        TransitionQueryFactory $queryFactory,
        WorkflowManager $workflowManager,
        EntityRepository $workflowItemRepository
    ) {
        $this->queryFactory = $queryFactory;
        $this->workflowManager = $workflowManager;
        $this->workflowItemRepository = $workflowItemRepository;
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsIdsByFilter($workflowName, $transitionName)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);

        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        if (!$transition instanceof Transition) {
            throw new \RuntimeException(
                sprintf('Can\'t get transition by given identifier "%s"', (string)$transitionName)
            );
        }

        $query = $this->queryFactory->create(
            $workflow,
            $transitionName,
            $transition->getScheduleFilter()
        );

        $result = $query->getArrayResult();

        $ids = [];
        foreach ($result as $row) {
            $ids[] = $row['id'];
        }

        // if needed - check conditions
        if ($ids && $transition->isScheduleCheckConditions()) {
            /** @var WorkflowItem[] $workflowItems */
            $workflowItems = $this->workflowItemRepository->findBy(['id' => $ids]);
            $ids = [];
            foreach ($workflowItems as $workflowItem) {
                if ($transition->isAllowed($workflowItem)) {
                    $ids[] = $workflowItem->getId();
                }
            }
        }

        return $ids;
    }

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

        $language->compile(
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
