<?php

namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that clears inactive mailbox.
 */
class ClearInactiveMailboxMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ImapClearManager $clearManager;

    private JobRunner $jobRunner;

    private LoggerInterface $logger;

    public function __construct(ImapClearManager $clearManager, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->clearManager = $clearManager;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->clearManager->setLogger($this->logger);

        $originId = null;

        $data = $message->getBody();
        if (isset($data['id'])) {
            $originId = $data['id'];
        }

        $this->jobRunner->runUniqueByMessage(
            $message,
            function () use ($originId) {
                $this->clearManager->clear($originId);

                return true;
            }
        );

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ClearInactiveMailboxTopic::getName()];
    }
}
