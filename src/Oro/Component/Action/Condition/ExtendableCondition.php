<?php

namespace Oro\Component\Action\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Exception\ExtendableEventNameMissingException;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

class ExtendableCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const DEFAULT_MESSAGE_TYPE = 'error';

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
     * @var FlashBag
     */
    protected $flashBag;

    /**
     * @var FlashBag
     */
    protected $translator;

    /**
     * @var bool
     */
    private $showErrors;

    /**
     * @var string
     */
    private $messageType;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param FlashBag $flashBag
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FlashBag $flashBag,
        TranslatorInterface $translator
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
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

        if ($event->hasErrors()) {
            $this->processErrors($event);

            return false;
        }

        return true;
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
                sprintf(
                    'You need to specify a list of event names for the "@%s" condition type with "events" config key',
                    self::NAME
                )
            );
        }
        $this->subscribedEvents = $options['events'];
        $this->showErrors = $options['showErrors'];
        $this->messageType = array_key_exists('messageType', $options)
            ? $options['messageType']
            : self::DEFAULT_MESSAGE_TYPE;
    }

    /**
     * @param ExtendableConditionEvent $event
     */
    protected function processErrors(ExtendableConditionEvent $event)
    {
        $errors = [];
        foreach ($event->getErrors() as $error) {
            $errors[] = $this->translator->trans($error['message']);
        }

        if ($this->contextAccessor->getValue($event->getContext(), $this->showErrors)) {
            foreach ($errors as $error) {
                $this->flashBag->add($this->messageType, $error);
            }
        } else {
            $event->getContext()->offsetSet('errors', $errors);
        }
    }
}
