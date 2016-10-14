<?php

namespace Oro\Component\Action\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

class ExtendableCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'workflow_extendable';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function isConditionAllowed($context)
    {
        if (is_object($context)) {
            $class = get_class($context);
            $sections = explode('\\', $class);
            $eventSubName = end($sections);
        } else {
            $eventSubName = 'general';
        }
        $eventName = sprintf('%s.%s', ExtendableConditionEvent::NAME, $eventSubName);
        if (!$this->eventDispatcher->hasListeners($eventName)) {
            return true;
        }

        $event = new ExtendableConditionEvent($context);
        $this->eventDispatcher->dispatch($eventName, $event);

        return !$event->hasErrors();
    }

    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        return;
    }
}
