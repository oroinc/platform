<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates visibilities for emails and email addresses in a specific organization.
 */
class UpdateVisibilitiesForOrganizationProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;
    private LoggerInterface $logger;

    public function __construct(
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_VISIBILITIES_FOR_ORGANIZATION];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['jobId'], $body['organizationId'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $this->processJob($body['organizationId']);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function processJob(int $organizationId): void
    {
        $this->emailAddressVisibilityManager->updateEmailAddressVisibilities($organizationId);

        $this->producer->send(
            Topics::UPDATE_EMAIL_VISIBILITIES_FOR_ORGANIZATION,
            ['organizationId' => $organizationId]
        );
    }
}
