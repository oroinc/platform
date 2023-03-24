<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationTopic;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that updates multiple emails for email owner.
 */
class UpdateEmailOwnerAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MessageProducerInterface $producer, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        asort($data['ownerIds']);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($data) {
                foreach ($data['ownerIds'] as $id) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s:%s', 'oro.email.update_email_owner_association', $data['ownerClass'], $id),
                        function (JobRunner $jobRunner, Job $child) use ($data, $id) {
                            $this->producer->send(
                                UpdateEmailOwnerAssociationTopic::getName(),
                                [
                                    'ownerId' => $id,
                                    'ownerClass' => $data['ownerClass'],
                                    'jobId' => $child->getId(),
                                ]
                            );
                        }
                    );
                }

                $this->logger->info(
                    sprintf('Sent "%s" messages', count($data['ownerIds'])),
                    ['data' => $data]
                );

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [UpdateEmailOwnerAssociationsTopic::getName()];
    }
}
