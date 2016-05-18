<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session;

class QueueMessageProcessor implements MessageProcessor
{
    /**
     * @var MessageProcessorRegistryInterface
     */
    protected $registry;

    /**
     * @param MessageProcessorRegistryInterface $registry
     */
    public function __construct(MessageProcessorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Session $session)
    {
        $processorName = $message->getProperty('processorName');
        if (false == $processorName) {
            throw new \LogicException('Got message without "processorName" parameter');
        }

        return $this->registry->get($processorName)->process($message, $session);
    }
}
