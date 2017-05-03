<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\ChainProcessor\Context;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

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
        $this->items = [
            self::IS_START => false,
            self::SAVED => false,
            self::PROCESSED => false,
            self::STATE => self::STATE_OK
        ];
    }

    /**
     * @param string $transitionName
     * @return TransitionContext
     */
    public function setTransitionName(string $transitionName): TransitionContext
    {
        $this->set(self::TRANSITION_NAME, $transitionName);

        return $this;
    }

    /**
     * @return string
     */
    public function getTransitionName(): string
    {
        return $this->get(self::TRANSITION_NAME);
    }

    /**
     * @param string $workflowName
     * @return TransitionContext
     */
    public function setWorkflowName(string $workflowName): TransitionContext
    {
        $this->set(self::WORKFLOW_NAME, $workflowName);

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkflowName(): string
    {
        return $this->get(self::WORKFLOW_NAME);
    }

    /**
     * @return Transition
     */
    public function getTransition(): Transition
    {
        return $this->transition;
    }

    /**
     * @param Transition $transition
     * @return TransitionContext
     */
    public function setTransition(Transition $transition): TransitionContext
    {
        $this->transition = $transition;

        return $this;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    /**
     * @param Workflow $workflow
     * @return TransitionContext
     */
    public function setWorkflow(Workflow $workflow): TransitionContext
    {
        $this->workflow = $workflow;

        return $this;
    }

    /**
     * @param bool $isStartTransition
     * @return TransitionContext
     */
    public function setIsStartTransition(bool $isStartTransition): TransitionContext
    {
        $this->set(self::IS_START, $isStartTransition);

        return $this;
    }

    /**
     * @return bool
     */
    public function isStartTransition(): bool
    {
        return $this->get(self::IS_START);
    }

    /**
     * @return bool
     */
    public function hasWorkflowItem(): bool
    {
        return null !== $this->workflowItem;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return TransitionContext
     */
    public function setWorkflowItem(WorkflowItem $workflowItem): TransitionContext
    {
        $this->workflowItem = $workflowItem;

        $this->setWorkflowName($workflowItem->getWorkflowName());

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return TransitionContext
     */
    public function setRequest(Request $request): TransitionContext
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param TransitActionResultTypeInterface $resultType
     * @return TransitionContext
     */
    public function setResultType(TransitActionResultTypeInterface $resultType): TransitionContext
    {
        $this->resultType = $resultType;
        $this->set(self::RESULT_TYPE, $resultType->getName());

        return $this;
    }

    /**
     * @return TransitActionResultTypeInterface
     */
    public function getResultType(): TransitActionResultTypeInterface
    {
        return $this->resultType;
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     * @return TransitionContext
     */
    public function setForm(FormInterface $form): TransitionContext
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @param bool $isCustomForm
     * @return TransitionContext
     */
    public function setIsCustomForm(bool $isCustomForm): TransitionContext
    {
        $this->set(self::CUSTOM_FORM, $isCustomForm);

        return $this;
    }

    /**
     * @return bool
     */
    public function isCustomForm(): bool
    {
        return $this->get(self::CUSTOM_FORM);
    }

    /**
     * @return bool
     */
    public function isSaved(): bool
    {
        return $this->get(self::SAVED);
    }

    /**
     * @param bool $saved
     * @return TransitionContext
     */
    public function setSaved(bool $saved): TransitionContext
    {
        $this->set(self::SAVED, $saved);

        return $this;
    }

    /**
     * @param bool $isProcessed
     * @return TransitionContext
     */
    public function setProcessed(bool $isProcessed): TransitionContext
    {
        $this->set(self::PROCESSED, $isProcessed);

        return $this;
    }

    /**
     * @return bool
     */
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

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return TransitionContext
     */
    public function setFormOptions(array $formOptions): TransitionContext
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return \Throwable
     */
    public function getError(): \Throwable
    {
        return $this->error;
    }

    /**
     * @param \Throwable $error
     * @return TransitionContext
     */
    public function setError(\Throwable $error): TransitionContext
    {
        $this->error = $error;
        $this->set(self::STATE, self::STATE_FAILURE);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return null !== $this->error;
    }
}
