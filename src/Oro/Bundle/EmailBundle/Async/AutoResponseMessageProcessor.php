<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponseTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that sends auto response for single email.
 */
class AutoResponseMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private AutoResponseManager $autoResponseManager;
    private JobRunner $jobRunner;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        AutoResponseManager $autoResponseManager,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->autoResponseManager = $autoResponseManager;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();

        /** @var Email $email */
        $email = $this->doctrine->getRepository(Email::class)->find($data['id']);
        if (! $email) {
            $this->logger->error(sprintf('Email was not found. id: "%s"', $data['id']));

            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($email) {
            $this->autoResponseManager->sendAutoResponses($email);

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [SendAutoResponseTopic::getName()];
    }
}
