<?php

namespace Oro\Component\Action\Event;

use Oro\Component\Action\Action\ActionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before and after action execution.
 *
 * This event carries the execution context and the action being executed, allowing listeners
 * to inspect or modify the context before or after action execution. Used with {@see ExecuteActionEvents}
 * constants to distinguish between pre-execution and post-execution events.
 */
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
