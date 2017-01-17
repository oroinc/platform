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

        if ($this->showErrors && $event->hasErrors()) {
            foreach ($event->getErrors() as $error) {
                $errorMessage = $this->translator->trans($error['message']);
                $this->flashBag->add($this->messageType, $errorMessage);
            }
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
                sprintf(
                    'You need to specify a list of event names for the "@%s" condition type with "events" config key',
                    self::NAME
                )
            );
        }
        $this->subscribedEvents = $options['events'];
        $this->showErrors = (bool) array_key_exists('showErrors', $options) ? $options['showErrors'] : false;
        $this->messageType = array_key_exists('messageType', $options)
            ? $options['messageType']
            : self::DEFAULT_MESSAGE_TYPE;
    }
}
