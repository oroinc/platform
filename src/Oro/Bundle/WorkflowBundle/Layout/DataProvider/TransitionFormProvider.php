<?php

namespace Oro\Bundle\WorkflowBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class TransitionFormProvider extends AbstractFormProvider
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function setWorkflowManager(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param $transitionName
     * @param WorkflowItem $workflowItem
     *
     * @return FormInterface
     *
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function getTransitionForm($transitionName, WorkflowItem $workflowItem)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $formType = $transition->getFormType();

        if ($transition->hasFormConfiguration()) {
            if (array_key_exists('form_init', $transition->getFormOptions())) {
                /** @var ActionInterface $action */
                $action = $transition->getFormOptions()['form_init'];
                $action->execute($workflowItem);
            }
            $formData = $workflowItem->getData()->get($transition->getFormDataAttribute());
            $formOptions = [];
        } else {
            $formData = $workflowItem->getData();
            $formOptions = array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                ]
            );
        }

        return $this->getForm($formType, $formData, $formOptions);
    }

    /**
     * @param $transitionName
     * @param WorkflowItem $workflowItem
     *
     * @return FormView
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function getTransitionFormView($transitionName, WorkflowItem $workflowItem)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $formType = $transition->getFormType();

        if ($transition->hasFormConfiguration()) {
            if (array_key_exists('form_init', $transition->getFormOptions())) {
                /** @var ActionInterface $action */
                $action = $transition->getFormOptions()['form_init'];
                $action->execute($workflowItem);
            }
            $formData = $workflowItem->getData()->get($transition->getFormDataAttribute());
            $formOptions = [];
        } else {
            $formData = $workflowItem->getData();
            $formOptions = array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                ]
            );
        }

        return $this->getFormView($formType, $formData, $formOptions);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCacheKey($type, array $formOptions = [], array $cacheKeyOptions = [])
    {
        //Unfortunately Workflow Item cannot be serialized
        if (isset($formOptions['workflow_item'])) {
            $formOptions['workflow_item'] = $formOptions['workflow_item']->getId();
        }

        return parent::getCacheKey($type, $formOptions, $cacheKeyOptions);
    }
}
