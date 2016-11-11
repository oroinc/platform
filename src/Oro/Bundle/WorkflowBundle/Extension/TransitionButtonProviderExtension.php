<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(WorkflowRegistry $workflowRegistry, DoctrineHelper $doctrineHelper)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        $buttons = [];
        $buttonContext = $this->generateButtonContext($buttonSearchContext);

        foreach ($this->workflowRegistry->getActiveWorkflows() as $workflow) {
            $transitionNames = [];
            if ($buttonSearchContext->getEntityClass()) {
                $entities = $workflow->getInitEntities();
                if (array_key_exists($buttonSearchContext->getEntityClass(), $entities)) {
                    $transitionNames = $entities[$buttonSearchContext->getEntityClass()];
                }
            }
            if ($buttonSearchContext->getRouteName()) {
                $routes = $workflow->getInitRoutes();
                if (array_key_exists($buttonSearchContext->getRouteName(), $routes)) {
                    $transitionNames = array_merge($transitionNames, $routes[$buttonSearchContext->getRouteName()]);
                }
            }

            /** @var Transition[] $transitions */
            $transitions = array_filter(
                $workflow->getTransitionManager()->getStartTransitions()->toArray(),
                function (Transition $transition) use ($transitionNames) {
                    return in_array($transition->getName(), $transitionNames, true);
                }
            );

            $workflowItem = $this->buildWorkflowItem($workflow);

            foreach ($transitions as $transition) {
                if ($transition->isAvailable(clone $workflowItem)) {
                    $buttons[] = new TransitionButton($transition, $workflow, $buttonContext);
                }
            }
        }

        return $buttons;
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext

     * @return WorkflowItem
     */
    protected function buildWorkflowItem(Workflow $workflow)
    {
        $workflowData = new WorkflowData([]);
        $workflowItem = new WorkflowItem();

        return $workflowItem->setEntityClass($workflow->getDefinition()->getRelatedEntity())
            ->setDefinition($workflow->getDefinition())
            ->setWorkflowName($workflow->getName())
            ->setData($workflowData);
    }

    /**
     * @param ButtonSearchContext $searchContext
     *
     * @return ButtonContext
     */
    protected function generateButtonContext(ButtonSearchContext $searchContext)
    {
        $context = new ButtonContext();
        $context->setDatagridName($searchContext->getGridName())
            ->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId())
            ->setRouteName($searchContext->getRouteName())
            ->setGroup($searchContext->getGroup());

        return $context;
    }
}
