<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent;
use Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents;
use Oro\Bundle\WorkflowBundle\Model\Condition\ConditionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Configurable implements ActionInterface
{
    const ALIAS = 'configurable';

    /**
     * @var ActionAssembler
     */
    protected $assembler;

    /**
     * @var ActionInterface
     */
    protected $action;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ActionAssembler $assembler
     */
    public function __construct(ActionAssembler $assembler)
    {
        $this->assembler = $assembler;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($context)
    {
        if (!$this->action) {
            $this->action = $this->assembler->assemble($this->configuration);
        }

        // dispatch oro_workflow.action.handle_before event
        $this->eventDispatcher->dispatch(
            ExecuteActionEvents::HANDLE_BEFORE,
            new ExecuteActionEvent($context, $this)
        );

        $this->action->execute($context);

        // dispatch oro_workflow.action.handle_after event
        $this->eventDispatcher->dispatch(
            ExecuteActionEvents::HANDLE_AFTER,
            new ExecuteActionEvent($context, $this)
        );
    }

    /**
     * Configurable action is always allowed
     *
     * {@inheritDoc}
     */
    public function setCondition(ConditionInterface $condition)
    {
    }
}
