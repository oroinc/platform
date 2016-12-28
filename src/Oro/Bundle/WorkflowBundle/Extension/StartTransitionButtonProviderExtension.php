<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class StartTransitionButtonProviderExtension extends AbstractButtonProviderExtension
{
    /**
     * {@inheritdoc}
     * @param StartTransitionButton $button
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        if (!$this->supports($button)) {
            throw $this->createUnsupportedButtonException($button);
        }

        $workflowItem = $this->buildWorkflowItem(
            $button->getTransition(),
            $button->getWorkflow(),
            $buttonSearchContext
        );

        try {
            $isAvailable = $button->getTransition()->isAvailable($workflowItem, $errors);
        } catch (\Exception $e) {
            $isAvailable = false;
            if (null !== $errors) {
                $errors->add(['message' => $e->getMessage(), 'parameters' => []]);
            }
        }

        return $isAvailable;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof StartTransitionButton && $button->getTransition()->isStart();
    }

    /**
     * @param Transition $transition
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return WorkflowItem
     */
    protected function buildWorkflowItem(Transition $transition, Workflow $workflow, ButtonSearchContext $searchContext)
    {
        $workflowItem = new WorkflowItem();

        return $workflowItem->setEntityClass($workflow->getDefinition()->getRelatedEntity())
            ->setDefinition($workflow->getDefinition())
            ->setWorkflowName($workflow->getName())
            ->setData(new WorkflowData([$transition->getInitContextAttribute() => $searchContext]));
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        $transitionNames = array_merge(
            $this->getNodeInitTransitions($searchContext->getEntityClass(), $workflow->getInitEntities()),
            $this->getNodeInitTransitions($searchContext->getRouteName(), $workflow->getInitRoutes()),
            $this->getNodeInitTransitions($searchContext->getDatagrid(), $workflow->getInitDatagrids())
        );

        return array_filter(
            $workflow->getTransitionManager()->getStartTransitions()->toArray(),
            function (Transition $transition) use ($transitionNames) {
                return in_array($transition->getName(), $transitionNames, true);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActiveWorkflows()
    {
        $exclusiveGroups = [];

        return parent::getActiveWorkflows()->filter(
            function (Workflow $workflow) use (&$exclusiveGroups) {
                $currentGroups = $workflow->getDefinition()->getExclusiveRecordGroups();

                if (array_intersect($exclusiveGroups, $currentGroups)) {
                    return false;
                }

                $exclusiveGroups = array_merge($exclusiveGroups, $currentGroups);

                return true;
            }
        );
    }

    /**
     * @param $value
     * @param array|null $data
     *
     * @return array
     */
    private function getNodeInitTransitions($value, array $data = null)
    {
        return ($data && array_key_exists($value, $data)) ? $data[$value] : [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    ) {
        return new StartTransitionButton($transition, $workflow, $buttonContext);
    }
}
