<?php

namespace Oro\Component\Action\Event;

use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\EventDispatcher\Event;

class ExecuteActionEvent extends Event
{
    /** @var mixed */
    protected $context;

    /** @var ActionInterface */
    protected $action;

    /**
     * @param mixed           $context
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
