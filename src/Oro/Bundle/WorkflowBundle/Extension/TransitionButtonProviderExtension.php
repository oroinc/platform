<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /**
     * @param WorkflowRegistry $workflowRegistry
     */
    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        $buttons = [];
        $buttonContext = $this->generateButtonContext($buttonSearchContext);
        foreach ($this->workflowRegistry->getActiveWorkflows() as $workflow) {
            foreach ($workflow->getTransitionManager()->getStartTransitions() as $transition) {
                $buttons[] = new TransitionButton($transition, $workflow, $buttonContext);
            }
        }

        return $buttons;
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
