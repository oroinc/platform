<?php

namespace Oro\Bundle\MessageQueueBundle\Log;

use ProxyManager\Proxy\ValueHolderInterface;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * This class can be used to extract the class name of executing message queue processor.
 */
class MessageProcessorClassProvider
{
    /** @var MessageProcessorRegistryInterface */
    private $messageProcessorRegistry;

    /**
     * @param MessageProcessorRegistryInterface $messageProcessorRegistry
     */
    public function __construct(MessageProcessorRegistryInterface $messageProcessorRegistry)
    {
        $this->messageProcessorRegistry = $messageProcessorRegistry;
    }

    /**
     * Gets the class name of the given message processor.
     *
     * @param MessageProcessorInterface $messageProcessor
     * @param MessageInterface          $message
     *
     * @return string
     */
    public function getMessageProcessorClass(MessageProcessorInterface $messageProcessor, MessageInterface $message)
    {
        if ($messageProcessor instanceof DelegateMessageProcessor) {
            $processorName = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
            if ($processorName) {
                try {
                    $messageProcessor = $this->messageProcessorRegistry->get($processorName);
                } catch (\Exception $e) {
                    // ignore any exception here
                }
            }
        }

        if ($messageProcessor instanceof ValueHolderInterface) {
            $messageProcessor = $messageProcessor->getWrappedValueHolderValue();
        }

        return get_class($messageProcessor);
    }
}
