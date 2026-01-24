<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Event\ExecuteActionEvent;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides common functionality for workflow and operation actions.
 *
 * This base class implements the core action execution flow including condition evaluation,
 * event dispatching, and context access. Subclasses must implement the `executeAction` method
 * to define the specific action logic.
 */
abstract class AbstractAction implements ActionInterface, EventDispatcherAwareActionInterface
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ExpressionInterface
     */
    protected $condition;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(ContextAccessor $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    #[\Override]
    public function setCondition(ExpressionInterface $condition)
    {
        $this->condition = $condition;
    }

    #[\Override]
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param mixed $context
     */
    #[\Override]
    public function execute($context)
    {
        if ($this->isAllowed($context)) {
            // dispatch oro_action.action.handle_before event
            $this->eventDispatcher->dispatch(
                new ExecuteActionEvent($context, $this),
                ExecuteActionEvents::HANDLE_BEFORE
            );

            $this->executeAction($context);

            // dispatch oro_action.action.handle_after event
            $this->eventDispatcher->dispatch(
                new ExecuteActionEvent($context, $this),
                ExecuteActionEvents::HANDLE_AFTER
            );
        }
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isAllowed($context)
    {
        if (!$this->condition) {
            return true;
        }

        return $this->condition->evaluate($context) ? true : false;
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed|null $default
     * @return null
     */
    protected function getOption(array $options, $key, $default = null)
    {
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    /**
     * @param mixed $context
     */
    abstract protected function executeAction($context);
}
