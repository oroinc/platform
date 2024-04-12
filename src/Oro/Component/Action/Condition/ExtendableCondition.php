<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks if the event has errors.
 *
 * Usage:
 *
 * @extendable:
 *     events: [extendable_condition.shopping_list_start]
 *     showErrors: true
 *     messageType: 'error'
 */
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
     * @var array
     */
    private $options;

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
     * {@inheritdoc}
     */
    public function isConditionAllowed($context)
    {
        $event = new ExtendableConditionEvent($context);
        foreach ($this->options['events'] as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $this->eventDispatcher->dispatch($event, $eventName);
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
        $resolver = $this->getOptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('events');
        $resolver->setDefined(['messageType', 'showErrors']);
        $resolver->setAllowedTypes('events', 'array');
        $resolver->setAllowedTypes('messageType', 'string');

        $resolver->setDefaults([
            'showErrors' => false,
            'messageType' => self::DEFAULT_MESSAGE_TYPE
        ]);
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver()
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
        }

        return $this->optionsResolver;
    }

    protected function processErrors(ExtendableConditionEvent $event)
    {
        $errors = [];
        foreach ($event->getErrors() as $error) {
            $errors[] = $this->translator->trans($error['message']);
            $this->errors[] = ['message' => $error['message'], 'parameters' => ($error['parameters'] ?? [])];
        }

        $showErrors = $this->contextAccessor->getValue($event->getContext(), $this->options['showErrors']);
        if ($showErrors) {
            foreach ($errors as $error) {
                $this->requestStack?->getSession()?->getFlashBag()->add($this->options['messageType'], $error);
            }
        }

        if ($event->getContext() instanceof \ArrayAccess) {
            $event->getContext()->offsetSet('errors', $errors);
        }
    }
}
