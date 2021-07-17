<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Watches console commands in order to enable the buffering mode at the beginning of command handling
 * and send all collected messages at the ending of command handling.
 */
class CommandWatcher implements EventSubscriberInterface
{
    /** @var BufferedMessageProducer */
    private $producer;

    public function __construct(BufferedMessageProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND   => ['onCommandStart', -250],
            /**
             * should be executed near at the end,
             * but before {@see \Symfony\Component\Console\EventListener\ErrorListener::onConsoleTerminate()}
             * @see \Symfony\Component\Console\EventListener\ErrorListener::getSubscribedEvents()
             */
            ConsoleEvents::TERMINATE => ['onCommandEnd', -125]
        ];
    }

    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (null === $command || $command instanceof ConsumeMessagesCommand) {
            return;
        }

        if (!$this->producer->isBufferingEnabled()) {
            $this->producer->enableBuffering();
        }
    }

    public function onCommandEnd(): void
    {
        if ($this->producer->isBufferingEnabled() && $this->producer->hasBufferedMessages()) {
            $this->producer->flushBuffer();
        }
    }
}
