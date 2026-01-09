<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Decorator;

use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\Error\ChannelActionErrorHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelActionEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelActionEventFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches channel action events and handles errors before delegating to the actual action handler.
 *
 * This decorator wraps a channel action handler and provides event dispatching capabilities.
 * It creates and dispatches a channel action event, handles any errors that occur during event
 * processing, and only delegates to the wrapped action handler if no errors were encountered.
 * This allows listeners to intercept and validate channel actions before they are executed.
 */
class ChannelActionHandlerDispatcherDecorator implements ChannelActionHandlerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ChannelActionEventFactoryInterface
     */
    private $eventFactory;

    /**
     * @var ChannelActionHandlerInterface
     */
    private $actionHandler;

    /**
     * @var ChannelActionErrorHandlerInterface
     */
    private $errorHandler;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ChannelActionEventFactoryInterface $eventFactory,
        ChannelActionHandlerInterface $actionHandler,
        ChannelActionErrorHandlerInterface $errorHandler
    ) {
        $this->dispatcher = $dispatcher;
        $this->eventFactory = $eventFactory;
        $this->actionHandler = $actionHandler;
        $this->errorHandler = $errorHandler;
    }

    #[\Override]
    public function handleAction(Channel $channel)
    {
        $event = $this->eventFactory->create($channel);

        $this->dispatcher->dispatch($event, $event->getName());

        if (!$this->handleEventErrors($event)) {
            return false;
        }

        $this->actionHandler->handleAction($channel);

        return true;
    }

    /**
     * @param ChannelActionEvent $event
     *
     * @return bool
     */
    private function handleEventErrors(ChannelActionEvent $event)
    {
        $errors = $event->getErrors();
        if ($errors->count() === 0) {
            return true;
        }

        $this->errorHandler->handleErrors($errors);

        return false;
    }
}
