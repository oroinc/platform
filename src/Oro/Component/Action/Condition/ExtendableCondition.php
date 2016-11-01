<?php

namespace Oro\Component\Action\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Exception\ExtendableEventNameMissingException;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

class ExtendableCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'extendable';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string[]
     */
    protected $subscribedEvents = [];

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
        $event = new ExtendableConditionEvent($context);
        foreach ($this->subscribedEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $this->eventDispatcher->dispatch($eventName, $event);
        }

        return false == $event->hasErrors();
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
        if (!array_key_exists('events', $options)) {
            throw new ExtendableEventNameMissingException(
                sprintf('You need to specify a list of event names for the "@%s" condition type with "events" config key', self::NAME)
            );
        }
        $this->subscribedEvents = $options['events'];
    }
}
