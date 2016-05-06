<?php
namespace Oro\Component\Messaging\EventDispatcher;

use Oro\Component\Messaging\ZeroConfig\ZeroConfig;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProducerEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var ZeroConfig
     */
    protected $zeroConfig;

    /**
     * @param ZeroConfig $zeroConfig
     */
    public function __construct(ZeroConfig $zeroConfig)
    {
        $this->zeroConfig = $zeroConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new MessageEvent();
        }

        if (false == $event instanceof MessageEvent) {
            throw new \LogicException('Invalid event instance. Expected instance of "Oro\Component\Messaging\EventDispatcher\MessageEvent"');
        }

        $body = json_encode($event->getValues());

        $this->zeroConfig->sendMessage($eventName, $body);
    }

    /**
     * {@inheritdoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        throw new \LogicException('Method is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        throw new \LogicException('Method is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($eventName, $listener)
    {
        throw new \LogicException('Method is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        throw new \LogicException('Method is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null)
    {
        throw new \LogicException('Method is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null)
    {
        throw new \LogicException('Method is not supported');
    }
}
