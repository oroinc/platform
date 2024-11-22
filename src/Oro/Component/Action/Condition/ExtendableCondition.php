<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableConditionEventFactoryInterface;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\ExtendableConditionEventErrorsProcessorInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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

    private ExtendableConditionEventErrorsProcessorInterface $errorsProcessor;

    /** @var array|ExtendableConditionEventFactoryInterface[] */
    private array $eventFactories = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * BC layer
     */
    public function addEventFactory(string $eventName, ExtendableConditionEventFactoryInterface $factory): void
    {
        $this->eventFactories[$eventName] = $factory;
    }

    public function setErrorsProcessor(ExtendableConditionEventErrorsProcessorInterface $errorsProcessor): void
    {
        $this->errorsProcessor = $errorsProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function isConditionAllowed($context)
    {
        $eventData = $this->getEventData($context);
        $subscribeEvents = $this->contextAccessor->getValue($context, $this->subscribedEvents);
        $hasErrors = false;
        foreach ($subscribeEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $event = $this->createEvent($eventName, $eventData);
            $this->eventDispatcher->dispatch($event, $eventName);

            if ($event->hasErrors()) {
                $this->processErrorsWithContext($context, $event);
                $hasErrors = true;
            }
        }

        return !$hasErrors;
    }

    /**
     * BC layer
     */
    private function createEvent(string $eventName, mixed $eventData): ExtendableConditionEvent
    {
        if (array_key_exists($eventName, $this->eventFactories)) {
            return $this->eventFactories[$eventName]->createEvent($eventData);
        }

        return new ExtendableConditionEvent($eventData);
    }

    private function getEventData($context)
    {
        if ($this->eventData) {
            return new ExtendableEventData($this->contextAccessor->getValue($context, $this->eventData));
        }

        return $context;
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

    /**
     * @deprecated
     */
    protected function processErrors(ExtendableConditionEvent $event)
    {
        $this->processErrorsWithContext($event->getContext(), $event);
    }

    private function processErrorsWithContext($context, ExtendableConditionEvent $event): void
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
