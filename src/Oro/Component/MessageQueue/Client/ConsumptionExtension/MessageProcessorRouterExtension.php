<?php

namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Finds a message processor name for message and puts it to the message queue context.
 */
class MessageProcessorRouterExtension extends AbstractExtension
{
    private TopicMetaRegistry $topicMetaRegistry;

    private DestinationMetaRegistry $destinationMetaRegistry;

    private string $noopMessageProcessorName;

    public function __construct(
        TopicMetaRegistry $topicMetaRegistry,
        DestinationMetaRegistry $destinationMetaRegistry,
        string $noopMessageProcessorName
    ) {
        $this->topicMetaRegistry = $topicMetaRegistry;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
        $this->noopMessageProcessorName = $noopMessageProcessorName;
    }

    public function onPreReceived(Context $context): void
    {
        if ($context->getMessageProcessorName()) {
            // Message processor is already set.
            return;
        }

        $topicName = $context->getMessage()?->getProperty(Config::PARAMETER_TOPIC_NAME) ?? '';
        $topicMeta = $this->topicMetaRegistry->getTopicMeta($topicName);

        $transportQueueName = $context->getMessage()?->getProperty(Config::PARAMETER_QUEUE_NAME) ?? '';
        $destinationMeta = $this->destinationMetaRegistry->getDestinationMetaByTransportQueueName($transportQueueName);

        $messageProcessorName = $topicMeta->getMessageProcessorName($destinationMeta->getQueueName());

        if (empty($messageProcessorName)) {
            // Falls back to noop message processor if message is not claimed by a message processor.
            $messageProcessorName = $this->noopMessageProcessorName;

            $context->getLogger()->warning(
                sprintf(
                    'Message processor for "%s" topic name in "%s" queue was not found, falling back to "%s"',
                    $topicName,
                    $destinationMeta->getQueueName(),
                    $this->noopMessageProcessorName
                )
            );
        } else {
            $context->getLogger()->debug(
                sprintf(
                    'Found "%s" message processor for topic "%s" in queue "%s"',
                    $messageProcessorName,
                    $topicName,
                    $destinationMeta->getQueueName()
                )
            );
        }

        $context->setMessageProcessorName($messageProcessorName);
    }
}
