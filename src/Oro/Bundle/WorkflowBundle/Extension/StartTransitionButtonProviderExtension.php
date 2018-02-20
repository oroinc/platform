<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class StartTransitionButtonProviderExtension extends AbstractStartTransitionButtonProviderExtension
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
            $this->addError($button, $e, $errors);
        }

        return $isAvailable;
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
        $workflowItem
            ->setEntityClass($workflow->getDefinition()->getRelatedEntity())
            ->setDefinition($workflow->getDefinition())
            ->setWorkflowName($workflow->getName())
            ->setData(new WorkflowData([$transition->getInitContextAttribute() => $searchContext]));

        // populate WorkflowData with variables
        if ($variables = $workflow->getVariables()) {
            foreach ($variables as $name => $variable) {
                $workflowItem->getData()->set($name, $variable->getValue());
            }
        }

        return $workflowItem;
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
    protected function getApplication()
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION;
    }
}
