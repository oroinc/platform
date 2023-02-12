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
    public const WORKFLOW_NAME = 'workflowName';
    public const TRANSITION_NAME = 'transitionName';
    public const IS_START = 'isStart';
    public const CUSTOM_FORM = 'customForm';
    public const HAS_INIT_OPTIONS = 'hasInitOptions';
    public const SAVED = 'saved';
    public const STATE = 'state';
    public const STATE_OK = 'ok';
    public const STATE_FAILURE = 'failure';
    public const INIT_DATA = 'initData';
    public const PROCESSED = 'processed';
    public const RESULT_TYPE = 'resultType';
    public const ENTITY_ID = 'entityId';

    private Workflow $workflow;
    private Transition $transition;
    private ?WorkflowItem $workflowItem = null;
    private Request $request;
    private TransitActionResultTypeInterface $resultType;
    private FormInterface $form;
    private mixed $formData = null;
    private array $formOptions = [];
    private ?\Throwable $error = null;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        parent::clear();
        $this->set(self::IS_START, false);
        $this->set(self::SAVED, false);
        $this->set(self::PROCESSED, false);
        $this->set(self::STATE, self::STATE_OK);
    }

    public function setTransitionName(string $transitionName): void
    {
        $this->set(self::TRANSITION_NAME, $transitionName);
    }

    public function getTransitionName(): string
    {
        return $this->get(self::TRANSITION_NAME);
    }

    public function setWorkflowName(string $workflowName): void
    {
        $this->set(self::WORKFLOW_NAME, $workflowName);
    }

    public function getWorkflowName(): string
    {
        return $this->get(self::WORKFLOW_NAME);
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }

    public function setTransition(Transition $transition): void
    {
        $this->transition = $transition;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    public function setIsStartTransition(bool $isStartTransition): void
    {
        $this->set(self::IS_START, $isStartTransition);
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

    public function setWorkflowItem(WorkflowItem $workflowItem): void
    {
        $this->workflowItem = $workflowItem;
        $this->setWorkflowName($workflowItem->getWorkflowName());
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function setResultType(TransitActionResultTypeInterface $resultType): void
    {
        $this->resultType = $resultType;
        $this->set(self::RESULT_TYPE, $resultType->getName());
    }

    public function getResultType(): TransitActionResultTypeInterface
    {
        return $this->resultType;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): void
    {
        $this->form = $form;
    }

    public function setIsCustomForm(bool $isCustomForm): void
    {
        $this->set(self::CUSTOM_FORM, $isCustomForm);
    }

    public function isCustomForm(): bool
    {
        return $this->get(self::CUSTOM_FORM);
    }

    public function isSaved(): bool
    {
        return $this->get(self::SAVED);
    }

    public function setSaved(bool $saved): void
    {
        $this->set(self::SAVED, $saved);
    }

    public function setProcessed(bool $isProcessed): void
    {
        $this->set(self::PROCESSED, $isProcessed);
    }

    public function isProcessed(): bool
    {
        return $this->get(self::PROCESSED);
    }

    public function getFormData(): mixed
    {
        return $this->formData;
    }

    public function setFormData(mixed $formData): void
    {
        $this->formData = $formData;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions): void
    {
        $this->formOptions = $formOptions;
    }

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function setError(\Throwable $error): void
    {
        $this->error = $error;
        $this->set(self::STATE, self::STATE_FAILURE);
    }

    public function hasError(): bool
    {
        return null !== $this->error;
    }
}
