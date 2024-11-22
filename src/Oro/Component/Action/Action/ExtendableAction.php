<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableActionEventFactoryInterface;
use Oro\Component\Action\Event\ExtendableEventData;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Triggers given event.
 *
 * Usage:
 *
 * @extendable:
 *     events: ['extendable_condition.shopping_list_start']
 *     eventData: { 'entity': $.someEntity }
 */
class ExtendableAction extends AbstractAction
{
    public const NAME = 'extendable';

    /**
     * @var string[]|PropertyPathInterface
     */
    protected $subscribedEvents;

    /**
     * @var array|PropertyPathInterface|null
     */
    protected $eventData;

    /** @var array|ExtendableActionEventFactoryInterface[] */
    private array $eventFactories = [];

    /**
     * BC layer
     */
    public function addEventFactory(string $eventName, ExtendableActionEventFactoryInterface $factory): void
    {
        $this->eventFactories[$eventName] = $factory;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $eventData = $this->getEventData($context);
        $subscribeEvents = $this->contextAccessor->getValue($context, $this->subscribedEvents);
        foreach ($subscribeEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $event = $this->createEvent($eventName, $eventData);
            $this->eventDispatcher->dispatch($event, $eventName);
        }
    }

    /**
     * BC layer
     */
    private function createEvent(string $eventName, mixed $eventData): ExtendableActionEvent
    {
        if (array_key_exists($eventName, $this->eventFactories)) {
            return $this->eventFactories[$eventName]->createEvent($eventData);
        }

        return new ExtendableActionEvent($eventData);
    }

    private function getEventData($context)
    {
        if ($this->eventData) {
            return new ExtendableEventData($this->contextAccessor->getValue($context, $this->eventData));
        }

        return $context;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('events');
        $optionsResolver->setAllowedTypes('events', ['array', PropertyPathInterface::class]);

        $optionsResolver->setDefined('eventData');
        $optionsResolver->setDefault('eventData', null);
        $optionsResolver->setAllowedTypes('eventData', ['array', PropertyPathInterface::class, 'null']);

        $resolvedOptions = $optionsResolver->resolve($options);

        $this->subscribedEvents = $resolvedOptions['events'];
        $this->eventData = $resolvedOptions['eventData'];

        return $this;
    }
}
