<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface;

class ExecuteActionEvent extends Event
{
    /** @var mixed */
    protected $context;

    /** @var ActionInterface */
    protected $action;

    /**
     * @param                 $context
     * @param ActionInterface $action
     */
    public function __construct($context, ActionInterface $action)
    {
        $this->context = $context;
        $this->action  = $action;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return ActionInterface
     */
    public function getAction()
    {
        return $this->action;
    }
}
