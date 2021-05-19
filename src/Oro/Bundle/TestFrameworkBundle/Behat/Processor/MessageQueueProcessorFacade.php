<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Message queue processor facade to return current processor
 */
class MessageQueueProcessorFacade implements MessageQueueProcessorInterface
{
    /** @var KernelInterface */
    private $kernel;

    /** @var MessageQueueProcessorInterface */
    private $dbalProcessor;

    /** @var MessageQueueProcessorInterface */
    private $amqpProcessor;

    public function __construct(
        KernelInterface $kernel,
        MessageQueueProcessorInterface $dbalProcessor,
        MessageQueueProcessorInterface $amqpProcessor
    ) {
        $this->kernel = $kernel;
        $this->dbalProcessor = $dbalProcessor;
        $this->amqpProcessor = $amqpProcessor;
    }

    public function startMessageQueue()
    {
        return $this->getMessageQueueProcessor()->startMessageQueue();
    }

    public function stopMessageQueue()
    {
        return $this->getMessageQueueProcessor()->stopMessageQueue();
    }

    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        return $this->getMessageQueueProcessor()->waitWhileProcessingMessages($timeLimit);
    }

    public function cleanUp()
    {
        return $this->getMessageQueueProcessor()->cleanUp();
    }

    public function isRunning()
    {
        return $this->getMessageQueueProcessor()->isRunning();
    }

    private function getMessageQueueProcessor(): MessageQueueProcessorInterface
    {
        $container = $this->kernel->getContainer();
        if ($container->getParameter('message_queue_transport') === 'amqp') {
            return $this->amqpProcessor;
        }

        return $this->dbalProcessor;
    }
}
