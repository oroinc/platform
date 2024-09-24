<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
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

    #[\Override]
    protected function executeAction($context)
    {
        $eventData = $this->getEventData($context);
        $event = new ExtendableActionEvent($eventData);
        $subscribeEvents = $this->contextAccessor->getValue($context, $this->subscribedEvents);
        foreach ($subscribeEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $this->eventDispatcher->dispatch($event, $eventName);
        }
    }

    private function getEventData($context): AbstractStorage
    {
        if ($this->eventData) {
            return new ExtendableEventData($this->contextAccessor->getValue($context, $this->eventData));
        }
        if ($context instanceof ActionDataStorageAwareInterface) {
            return $context->getActionDataStorage();
        }
        if ($context instanceof AbstractStorage) {
            return $context;
        }
        if (\is_array($context)) {
            return new ExtendableEventData($context);
        }

        throw new \RuntimeException('Unsupported context given');
    }

    #[\Override]
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
