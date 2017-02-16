<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Decorator;

use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\Error\ChannelActionErrorHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelActionEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelActionEventFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var ChannelActionErrorHandlerInterface|null
     */
    private $errorHandler;

    /**
     * @param EventDispatcherInterface           $dispatcher
     * @param ChannelActionEventFactoryInterface $eventFactory
     * @param ChannelActionHandlerInterface      $actionHandler
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        ChannelActionEventFactoryInterface $eventFactory,
        ChannelActionHandlerInterface $actionHandler
    ) {
        $this->dispatcher = $dispatcher;
        $this->eventFactory = $eventFactory;
        $this->actionHandler = $actionHandler;
    }

    /**
     * @param ChannelActionErrorHandlerInterface $errorHandler
     */
    public function setErrorHandler(ChannelActionErrorHandlerInterface $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function handleAction(Channel $channel)
    {
        $event = $this->eventFactory->create($channel);

        $this->dispatcher->dispatch($event->getName(), $event);

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

        if ($this->errorHandler) {
            $this->errorHandler->handleErrors($errors);
        }

        return false;
    }
}
