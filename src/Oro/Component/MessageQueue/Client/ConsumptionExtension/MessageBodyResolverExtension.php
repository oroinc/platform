<?php

namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\MessageBodyResolverInterface;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;

/**
 * Message queue extension that resolves the message body using message body resolver.
 * Skips those messages whose topics are not registered in {@see TopicRegistry}.
 */
class MessageBodyResolverExtension extends AbstractExtension
{
    private MessageBodyResolverInterface $messageBodyResolver;

    private TopicRegistry $topicRegistry;

    public function __construct(TopicRegistry $topicRegistry, MessageBodyResolverInterface $messageBodyResolver)
    {
        $this->topicRegistry = $topicRegistry;
        $this->messageBodyResolver = $messageBodyResolver;
    }

    public function onPreReceived(Context $context): void
    {
        parent::onPreReceived($context);

        $topicName = $context->getMessage()?->getProperty(Config::PARAMETER_TOPIC_NAME) ?? '';
        if (!$topicName || !$this->topicRegistry->has($topicName)) {
            // Skips resolving of the message body as its topic is not present in {@see TopicRegistry}.
            return;
        }

        $message = $context->getMessage();

        try {
            $message->setBody($this->messageBodyResolver->resolveBody($topicName, $message->getBody()));
        } catch (InvalidMessageBodyException $exception) {
            $context->getLogger()->error(
                sprintf('Message is rejected. %s', $exception->getMessage()),
                ['exception' => $exception, 'topic' => $topicName, 'message' => $message->getBody()]
            );

            // Rejects message by setting a rejected status to context.
            $context->setStatus(MessageProcessorInterface::REJECT);
        }
    }
}
