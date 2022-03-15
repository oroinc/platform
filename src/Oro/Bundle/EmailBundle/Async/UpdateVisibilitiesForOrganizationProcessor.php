<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Updates visibilities for emails and email addresses for a specific organization.
 */
class UpdateVisibilitiesForOrganizationProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;

    public function __construct(
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        MessageProducerInterface $producer,
        JobRunner $jobRunner
    ) {
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [UpdateVisibilitiesForOrganizationTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        $result = $this->jobRunner->runDelayed(
            $data['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($data) {
                $this->processJob($data['organizationId']);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function processJob(int $organizationId): void
    {
        $this->emailAddressVisibilityManager->updateEmailAddressVisibilities($organizationId);

        $this->producer->send(
            UpdateEmailVisibilitiesForOrganizationTopic::getName(),
            ['organizationId' => $organizationId]
        );
    }
}
