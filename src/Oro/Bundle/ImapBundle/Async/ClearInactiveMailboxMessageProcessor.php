<?php

namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ClearInactiveMailboxMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ImapClearManager */
    private $clearManager;

    /** @var JobRunner */
    private $jobRunner;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ImapClearManager $clearManager
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
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

        $data = JSON::decode($message->getBody());
        if (isset($data['id'])) {
            $originId = $data['id'];
        }

        $this->jobRunner->runUnique(
            $message->getMessageId(),
            Topics::CLEAR_INACTIVE_MAILBOX,
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
        return [Topics::CLEAR_INACTIVE_MAILBOX];
    }
}
