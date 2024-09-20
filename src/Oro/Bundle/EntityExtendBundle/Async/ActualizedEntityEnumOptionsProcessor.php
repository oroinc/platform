<?php

namespace Oro\Bundle\EntityExtendBundle\Async;

use Oro\Bundle\EntityExtendBundle\Async\Topic\ActualizeEntityEnumOptionsTopic;
use Oro\Bundle\EntityExtendBundle\Tools\EntityEnumOptionsActualizer;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processor for actualized entity enum options value
 */
class ActualizedEntityEnumOptionsProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly EntityEnumOptionsActualizer $entityEnumOptionsActualizer)
    {
        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $messageData = $message->getBody();
            $this->entityEnumOptionsActualizer->run(
                $messageData[ActualizeEntityEnumOptionsTopic::ENUM_CODE],
                $messageData[ActualizeEntityEnumOptionsTopic::ENUM_OPTION_ID]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $exception,
                    'topic' => ActualizeEntityEnumOptionsTopic::getName(),
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [ActualizeEntityEnumOptionsTopic::getName()];
    }
}
