<?php

namespace Oro\Bundle\WorkflowBundle\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class AbstractTransitionButton implements ButtonInterface
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
        return array_merge(
            [
                'workflow' => $this->workflow,
                'transition' => $this->transition,
                'context' => $this->getButtonContext()
            ],
            $customData
        );
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
        return ButtonInterface::DEFAULT_GROUP;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @return Transition
     */
    public function getTransition()
    {
        return $this->transition;
    }
}
