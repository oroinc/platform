<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\ChainProcessor\Context;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The execution context for processors for "transit" action.
 */
class TransitionContext extends Context
{
    const WORKFLOW_NAME = 'workflowName';
    const TRANSITION_NAME = 'transitionName';
    const IS_START = 'isStart';
    const CUSTOM_FORM = 'customForm';
    const HAS_INIT_OPTIONS = 'hasInitOptions';
    const SAVED = 'saved';
    const STATE = 'state';
    const STATE_OK = 'ok';
    const STATE_FAILURE = 'failure';
    const INIT_DATA = 'initData';
    const PROCESSED = 'processed';
    const RESULT_TYPE = 'resultType';
    const ENTITY_ID = 'entityId';

    /** @var Workflow */
    protected $workflow;

    /** @var Transition */
    protected $transition;

    /** @var WorkflowItem */
    protected $workflowItem;

    /** @var Request */
    protected $request;

    /** @var TransitActionResultTypeInterface */
    protected $resultType;

    /** @var FormInterface */
    protected $form;

    /** @var mixed */
    protected $formData;

    /** @var array */
    protected $formOptions = [];

    /** @var \Throwable */
    protected $error;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        parent::clear();
        $this->set(self::IS_START, false);
        $this->set(self::SAVED, false);
        $this->set(self::PROCESSED, false);
        $this->set(self::STATE, self::STATE_OK);
    }

    public function setTransitionName(string $transitionName): TransitionContext
    {
        $this->set(self::TRANSITION_NAME, $transitionName);

        return $this;
    }

    public function getTransitionName(): string
    {
        return $this->get(self::TRANSITION_NAME);
    }

    public function setWorkflowName(string $workflowName): TransitionContext
    {
        $this->set(self::WORKFLOW_NAME, $workflowName);

        return $this;
    }

    public function getWorkflowName(): string
    {
        return $this->get(self::WORKFLOW_NAME);
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }

    public function setTransition(Transition $transition): TransitionContext
    {
        $this->transition = $transition;

        return $this;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(Workflow $workflow): TransitionContext
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function setIsStartTransition(bool $isStartTransition): TransitionContext
    {
        $this->set(self::IS_START, $isStartTransition);

        return $this;
    }

    public function isStartTransition(): bool
    {
        return $this->get(self::IS_START);
    }

    public function hasWorkflowItem(): bool
    {
        return null !== $this->workflowItem;
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    public function setWorkflowItem(WorkflowItem $workflowItem): TransitionContext
    {
        $this->workflowItem = $workflowItem;

        $this->setWorkflowName($workflowItem->getWorkflowName());

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): TransitionContext
    {
        $this->request = $request;

        return $this;
    }

    public function setResultType(TransitActionResultTypeInterface $resultType): TransitionContext
    {
        $this->resultType = $resultType;
        $this->set(self::RESULT_TYPE, $resultType->getName());

        return $this;
    }

    public function getResultType(): TransitActionResultTypeInterface
    {
        return $this->resultType;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): TransitionContext
    {
        $this->form = $form;

        return $this;
    }

    public function setIsCustomForm(bool $isCustomForm): TransitionContext
    {
        $this->set(self::CUSTOM_FORM, $isCustomForm);

        return $this;
    }

    public function isCustomForm(): bool
    {
        return $this->get(self::CUSTOM_FORM);
    }

    public function isSaved(): bool
    {
        return $this->get(self::SAVED);
    }

    public function setSaved(bool $saved): TransitionContext
    {
        $this->set(self::SAVED, $saved);

        return $this;
    }

    public function setProcessed(bool $isProcessed): TransitionContext
    {
        $this->set(self::PROCESSED, $isProcessed);

        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->get(self::PROCESSED);
    }

    /**
     * @return mixed
     */
    public function getFormData()
    {
        return $this->formData;
    }

    /**
     * @param mixed $formData
     * @return TransitionContext
     */
    public function setFormData($formData): TransitionContext
    {
        $this->formData = $formData;

        return $this;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions): TransitionContext
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function setError(\Throwable $error): TransitionContext
    {
        $this->error = $error;
        $this->set(self::STATE, self::STATE_FAILURE);

        return $this;
    }

    public function hasError(): bool
    {
        return null !== $this->error;
    }
}
