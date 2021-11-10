<?php

namespace Oro\Component\MessageQueue\Log;

use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * This class can be used to extract the class name by message queue processor name.
 */
class MessageProcessorClassProvider
{
    private MessageProcessorRegistryInterface $messageProcessorRegistry;

    private array $messageProcessorClassByName = [];

    public function __construct(MessageProcessorRegistryInterface $messageProcessorRegistry)
    {
        $this->messageProcessorRegistry = $messageProcessorRegistry;
    }

    /**
     * Gets the class name of the message processor by its name.
     *
     * @param string $messageProcessorName
     *
     * @return string
     */
    public function getMessageProcessorClassByName(string $messageProcessorName): string
    {
        if (!isset($this->messageProcessorClassByName[$messageProcessorName])) {
            $messageProcessor = $this->messageProcessorRegistry->get($messageProcessorName);
            if ($messageProcessor instanceof ValueHolderInterface) {
                if ($messageProcessor instanceof LazyLoadingInterface && !$messageProcessor->isProxyInitialized()) {
                    $messageProcessor->initializeProxy();
                }

                $messageProcessor = $messageProcessor->getWrappedValueHolderValue();
            }

            $this->messageProcessorClassByName[$messageProcessorName] = get_class($messageProcessor);
        }

        return $this->messageProcessorClassByName[$messageProcessorName];
    }
}
