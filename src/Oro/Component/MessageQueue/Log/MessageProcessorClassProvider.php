<?php

namespace Oro\Component\MessageQueue\Log;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * This class can be used to extract the class name of executing message queue processor.
 */
class MessageProcessorClassProvider
{
    /** @var MessageProcessorRegistryInterface */
    private $messageProcessorRegistry;

    /** @var string|null */
    private $lastProcessorKey;

    /** @var string|null */
    private $lastProcessorClass;

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
                if ($processorName === $this->lastProcessorKey) {
                    return $this->lastProcessorClass;
                }

                $this->lastProcessorKey = $processorName;
                try {
                    $messageProcessor = $this->messageProcessorRegistry->get($processorName);
                } catch (\Exception $e) {
                    // ignore any exception here
                }
            }
        } else {
            $processorHash = spl_object_hash($messageProcessor);
            if ($processorHash === $this->lastProcessorKey) {
                return $this->lastProcessorClass;
            }

            $this->lastProcessorKey = $processorHash;
        }

        if ($messageProcessor instanceof ValueHolderInterface) {
            $messageProcessor = $messageProcessor->getWrappedValueHolderValue();
        }

        $this->lastProcessorClass = get_class($messageProcessor);

        return $this->lastProcessorClass;
    }
}
