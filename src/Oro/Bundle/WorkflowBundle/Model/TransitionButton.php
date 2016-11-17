<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class TransitionButton implements ButtonInterface
{
    const DEFAULT_TEMPLATE = 'OroWorkflowBundle::Button\transitionButton.html.twig';

    /** @var Workflow */
    protected $workflow;

    /** @var Transition */
    protected $transition;

    /*** @var ButtonContext */
    protected $buttonContext;

    /**
     * @param Transition $transition
     * @param Workflow $workflow
     * @param ButtonContext $buttonContext
     */
    public function __construct(Transition $transition, Workflow $workflow, ButtonContext $buttonContext)
    {
        $this->transition = $transition;
        $this->workflow = $workflow;
        $this->buttonContext = $buttonContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return $this->workflow->getDefinition()->getPriority();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return static::DEFAULT_TEMPLATE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData(array $customData = [])
    {
        return [
            'workflow' => $this->workflow,
            'transition' => $this->transition,
            'workflowItem' => null,
            'transitionData' => [
                'workflow' => $this->workflow->getName(),
                'transition' => $this->transition->getName(),
                'dialog-route' => $this->buttonContext->getDialogUrl(),
                'transition-route' => $this->buttonContext->getExecutionUrl(),
                'transition-condition-messages' => null,
                'isAllowed' => true,
                'enabled' => $this->buttonContext->isEnabled() ?: $this->buttonContext->isUnavailableHidden(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonContext()
    {
        return $this->buttonContext;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return OperationRegistry::DEFAULT_GROUP;
    }
}
