<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use Oro\Component\Action\Model\ExtendableConditionEventErrorsProcessorInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Checks that condition is allowed when all listeners add no data to the event object.
 *
 * Usage:
 *
 * @extendable:
 *     events: [extendable_condition.shopping_list_start]
 *     eventData: { 'entity': $.someEntity }
 *     showErrors: true
 *     messageType: 'error'
 */
class ExtendableCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    public const DEFAULT_MESSAGE_TYPE = 'error';
    public const NAME = 'extendable';

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var string[]|PropertyPathInterface
     */
    private $subscribedEvents;

    /**
     * @var array|PropertyPathInterface|null
     */
    private $eventData;

    /**
     * @var string|PropertyPathInterface
     */
    private $messageType;

    /**
     * @var bool|PropertyPathInterface
     */
    private $showErrors;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ExtendableConditionEventErrorsProcessorInterface $errorsProcessor
    ) {
    }

    #[\Override]
    public function isConditionAllowed($context)
    {
        $eventData = $this->getEventData($context);
        $event = new ExtendableConditionEvent($eventData);

        $subscribeEvents = $this->contextAccessor->getValue($context, $this->subscribedEvents);
        foreach ($subscribeEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $this->eventDispatcher->dispatch($event, $eventName);
        }

        if ($event->hasErrors()) {
            $this->processErrors($context, $event);

            return false;
        }

        return true;
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

    /**
     * Returns the expression name.
     *
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return static::NAME;
    }

    #[\Override]
    public function initialize(array $options)
    {
        $resolver = $this->getOptionsResolver();
        $this->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve($options);

        $this->subscribedEvents = $resolvedOptions['events'];
        $this->eventData = $resolvedOptions['eventData'];
        $this->messageType = $resolvedOptions['messageType'];
        $this->showErrors = $resolvedOptions['showErrors'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('events');
        $resolver->setDefined(['messageType', 'showErrors']);
        $resolver->setAllowedTypes('events', ['array', PropertyPathInterface::class]);
        $resolver->setAllowedTypes('messageType', ['string', PropertyPathInterface::class]);

        $resolver->setDefined('eventData');
        $resolver->setDefault('eventData', null);
        $resolver->setAllowedTypes('eventData', ['array', PropertyPathInterface::class, 'null']);

        $resolver->setDefaults([
            'showErrors' => false,
            'messageType' => self::DEFAULT_MESSAGE_TYPE
        ]);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
        }

        return $this->optionsResolver;
    }

    private function processErrors($context, ExtendableConditionEvent $event): void
    {
        $translatedErrors = $this->errorsProcessor->processErrors(
            $event,
            (bool)$this->contextAccessor->getValue($context, $this->showErrors),
            $this->errors,
            (string)$this->contextAccessor->getValue($context, $this->messageType)
        );
        $event->getData()?->offsetSet('errors', $translatedErrors);
    }
}
